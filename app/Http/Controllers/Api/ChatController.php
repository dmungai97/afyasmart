<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = $request->user();

        // --- Temporary mock when API key is not set ---
        if (config('app.use_ai_mock') || empty(config('services.anthropic.key'))) {
            $aiReply = $this->mockReply(strtolower($request->message));

            ChatLog::create([
                'user_id' => $user->id,
                'message' => $request->message,
                'reply'   => $aiReply,
            ]);

            return response()->json(['status' => 'success', 'reply' => $aiReply]);
        }
        // -----------------------------------------------

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5',   // ← fixed
            'max_tokens' => 1024,
            'system'     => 'You are AfyaSmart AI, a helpful health assistant for users in Kenya. Provide clear, accurate general health information. Always remind users to consult a licensed doctor for diagnosis or treatment.',
            'messages'   => [
                ['role' => 'user', 'content' => $request->message],
            ],
        ]);

        if (!$response->successful()) {
            \Log::error('Anthropic error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return response()->json([
                'status' => 'error',
                'reply'  => 'Sorry, the AI service is unavailable. Please try again later.',
            ], 503);
        }

        $aiReply = $response->json('content.0.text')
            ?? 'Sorry, I could not process your request.';

        ChatLog::create([
            'user_id' => $user->id,
            'message' => $request->message,
            'reply'   => $aiReply,
        ]);

        return response()->json(['status' => 'success', 'reply' => $aiReply]);
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

    public function history(Request $request)
    {
        $logs = ChatLog::where('user_id', $request->user()->id)
            ->latest()
            ->take(50)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $logs,
        ]);
    }
}