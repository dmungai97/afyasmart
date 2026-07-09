<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    const FREE_CHAT_LIMIT = 5;

    /**
     * Resolve the calling user from the firebase_uid field in the request body.
     * Returns null if no matching user is found — callers decide how to handle that.
     */
    private function resolveUser(Request $request): ?User
    {
        $uid = $request->input('firebase_uid');
        if (!$uid) return null;

        return User::where('firebase_uid', $uid)->first();
    }

    public function send(Request $request)
    {
        $request->validate([
            'message'      => 'required|string|max:1000',
            'firebase_uid' => 'required|string',
        ]);

        $user = $this->resolveUser($request);

        // ── Limit check (skip if user not found in Laravel — new Firebase-only user) ──
        if ($user && !$user->is_subscribed && $user->chat_count >= self::FREE_CHAT_LIMIT) {
            return response()->json([
                'status'        => 'error',
                'limit_reached' => true,
                'message'       => 'Free chat limit reached. Subscribe to continue.',
                'chat_count'    => $user->chat_count,
                'limit'         => self::FREE_CHAT_LIMIT,
            ], 403);
        }

        // ── AI model selection ────────────────────────────────────
        $openaiKey = config('services.openai.key');
        $useMock   = config('app.use_ai_mock') || empty($openaiKey);

        if ($useMock) {
            $aiReply = $this->mockReply(strtolower($request->message));

        } else {
            // ── OpenAI gpt-4o-mini ────────────────────────────────
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model'      => 'gpt-4o-mini',
                'max_tokens' => 1024,
                'messages'   => [
                    [
                        'role'    => 'system',
                        'content' => 'You are AfyaSmart AI, a helpful health assistant for users in Kenya. You must ONLY answer questions that are related to medicine, health, symptoms, wellness, drugs, pharmacies, or general healthcare. If a user asks about anything else (e.g., general knowledge, programming, writing tasks, or non-health topics), you must politely but firmly decline to answer, explaining that you are a medical assistant and can only help with health-related queries. Always remind users to consult a licensed doctor for diagnosis or treatment.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $request->message,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                \Log::error('OpenAI error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['status' => 'error', 'reply' => 'Sorry, the AI service is unavailable. Please try again later.'], 503);
            }

            $aiReply = $response->json('choices.0.message.content')
                ?? 'Sorry, I could not process your request.';
        }

        // ── Save log + increment count (only if user exists in Laravel) ──
        if ($user) {
            ChatLog::create([
                'user_id' => $user->id,
                'message' => $request->message,
                'reply'   => $aiReply,
            ]);

            $user->increment('chat_count');
        }

        return response()->json([
            'status'        => 'success',
            'reply'         => $aiReply,
            'chat_count'    => $user?->fresh()->chat_count ?? 0,
            'limit'         => self::FREE_CHAT_LIMIT,
            'is_subscribed' => $user?->is_subscribed ?? false,
        ]);
    }

    public function status(Request $request)
    {
        $request->validate(['firebase_uid' => 'required|string']);

        $user = $this->resolveUser($request);

        $chatCount    = $user?->chat_count ?? 0;
        $isSubscribed = $user?->is_subscribed ?? false;

        return response()->json([
            'status'        => 'success',
            'chat_count'    => $chatCount,
            'limit'         => self::FREE_CHAT_LIMIT,
            'is_subscribed' => $isSubscribed,
            'limit_reached' => !$isSubscribed && $chatCount >= self::FREE_CHAT_LIMIT,
            'remaining'     => max(0, self::FREE_CHAT_LIMIT - $chatCount),
        ]);
    }

    public function history(Request $request)
    {
        $request->validate(['firebase_uid' => 'required|string']);

        $user = $this->resolveUser($request);

        if (!$user) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $logs = ChatLog::where('user_id', $user->id)
            ->latest()
            ->take(50)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $logs,
        ]);
    }

    private function mockReply(string $message): string
    {
        if (str_contains($message, 'headache')) {
            return 'Headaches can be caused by dehydration, stress, or lack of sleep. Try drinking water and resting. If it persists, consult a doctor.';
        }
        if (str_contains($message, 'fever')) {
            return 'A fever above 38°C may indicate infection. Rest, stay hydrated, and seek medical attention if it exceeds 39.5°C or lasts more than 3 days.';
        }
        if (str_contains($message, 'hello') || str_contains($message, 'hi')) {
            return 'Hello! I am AfyaSmart AI. How can I help you with your health question today?';
        }
        return 'Thank you for your question. For accurate medical advice, please consult a licensed healthcare provider.';
    }
}
