<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SymptomController extends Controller
{
    const FREE_DAILY_LIMIT = 3;

    public function analyze(Request $request)
    {
        $request->validate([
            'firebase_uid' => 'required|string',
            'symptoms'     => 'required|array|min:1',
            'symptoms.*'   => 'required|string|max:100',
            'age'          => 'required|integer|min:0|max:120',
            'gender'       => 'required|string|in:male,female,other',
            'duration'     => 'required|string|max:50',
            'severity'     => 'required|string|max:50',
            'answers'      => 'nullable|array',
        ]);

        $uid  = $request->firebase_uid;
        $user = User::firstOrCreate(
            ['firebase_uid' => $uid],
            [
                'name'                    => 'AfyaSmart User',
                'email'                   => $uid . '@afyasmart.local',
                'password'                => bcrypt(\Illuminate\Support\Str::random(16)),
                'is_subscribed'           => false,
                'chat_count'              => 0,
                'subscription_expires_at' => null,
            ]
        );

        // ── 1. Check Spam / Daily Quota limits (skip if user is subscribed) ──
        $isSubscribed = $user?->is_subscribed ?? false;

        if (!$isSubscribed) {
            $today = date('Y-m-d');
            $cacheKey = "symptom_checks_{$uid}_{$today}";
            $checksToday = (int) Cache::get($cacheKey, 0);

            if ($checksToday >= self::FREE_DAILY_LIMIT) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Daily free check limit reached. Subscribe for unlimited checks.',
                ], 429);
            }

            Cache::put($cacheKey, $checksToday + 1, now()->endOfDay());
        }

        // ── 2. OpenAI dynamic diagnosis request ──
        $openaiKey = config('services.openai.key');
        if (empty($openaiKey) || config('app.use_ai_mock')) {
            return $this->mockResponse();
        }

        try {
            $symptomList = implode(', ', $request->symptoms);
            $genderText  = $request->gender;
            $ageVal      = $request->age;
            $durationVal = $request->duration;
            $severityVal = $request->severity;
            $answersText = '';

            if (!empty($request->answers)) {
                foreach ($request->answers as $qId => $ans) {
                    $answersText .= "- Question ID {$qId}: Answer: {$ans}\n";
                }
            }

            $prompt = "Analyze the following patient profile and symptom context:\n" .
                      "- Symptoms: {$symptomList}\n" .
                      "- Age: {$ageVal} years old\n" .
                      "- Gender: {$genderText}\n" .
                      "- Duration: {$durationVal}\n" .
                      "- Severity: {$severityVal}\n" .
                      (empty($answersText) ? "" : "- Follow-up Questions:\n{$answersText}") .
                      "\nBased on this information, provide the top 3 possible medical conditions (with likelihood and probability percentage), self-care instructions, and commonly suggested medications/remedies.";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model'           => 'gpt-4o-mini',
                'max_tokens'      => 1020,
                'temperature'     => 0.3, // Lower temp for more deterministic output
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    [
                        'role'    => 'system',
                        'content' => 'You are a professional medical analysis assistant. Your role is to suggest possible conditions based on symptoms.
                                      You must return a raw JSON response representing the diagnosis. 
                                      
                                      JSON SCHEMA:
                                      {
                                        "urgency": "High" | "Medium" | "Low",
                                        "urgency_desc": "Explanation of urgency based on symptoms.",
                                        "conditions": [
                                          { "name": "Condition Name", "likelihood": "High" | "Medium" | "Low", "percent": integer_between_0_and_100, "color": "#EF4444" for High | "#F59E0B" for Medium | "#0B6E6E" for Low }
                                        ],
                                        "medications": [
                                          { "name": "Medication Name", "desc": "Short description of what it does", "icon": "💊" | "🧃" | "💊" }
                                        ],
                                        "self_care": [
                                          "Actionable advice line 1",
                                          "Actionable advice line 2"
                                        ]
                                      }
                                      Do not include any text, backticks, or wrapping outside the JSON object.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI Symptoms check failed', ['status' => $response->status(), 'body' => $response->body()]);
                return $this->mockResponse();
            }

            $jsonData = json_decode($response->json('choices.0.message.content'), true);
            if (empty($jsonData) || !isset($jsonData['conditions'])) {
                Log::warning('OpenAI Symptoms check returned invalid JSON structure', ['content' => $response->json('choices.0.message.content')]);
                return $this->mockResponse();
            }

            return response()->json([
                'status' => 'success',
                'data'   => $jsonData,
            ]);

        } catch (\Throwable $e) {
            Log::error('SymptomsController execution failed', ['error' => $e->getMessage()]);
            return $this->mockResponse();
        }
    }

    private function mockResponse()
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'urgency'      => 'High',
                'urgency_desc' => 'Your symptoms may need medical attention. Seek advice from a professional.',
                'conditions'   => [
                    ['name' => 'Malaria',        'likelihood' => 'High',   'percent' => 80, 'color' => '#EF4444'],
                    ['name' => 'Flu (Influenza)', 'likelihood' => 'Medium', 'percent' => 45, 'color' => '#F59E0B'],
                    ['name' => 'Typhoid',        'likelihood' => 'Low',    'percent' => 20, 'color' => '#0B6E6E'],
                ],
                'medications' => [
                    ['name' => 'Paracetamol 500mg', 'desc' => 'For fever and pain',        'icon' => '💊'],
                    ['name' => 'ORS',               'desc' => 'To prevent dehydration',    'icon' => '🧃'],
                    ['name' => 'Antimalarial',      'desc' => 'Seek doctor\'s advice first', 'icon' => '💉'],
                ],
                'self_care' => [
                    'Rest and drink plenty of fluids',
                    'Take paracetamol for fever',
                    'Eat light and healthy meals',
                ],
            ]
        ]);
    }
}
