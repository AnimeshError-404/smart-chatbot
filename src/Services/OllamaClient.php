<?php

namespace Ridgeben\SmartChatbot\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OllamaClient
{
    public function ask(string $prompt): string
    {
        try {
            $url = rtrim(config('smart-chatbot.ollama.url'), '/') . '/api/chat';

            $response = Http::timeout(config('smart-chatbot.ollama.timeout', 180))
                ->post($url, [
                    'model' => config('smart-chatbot.ollama.model'),
                    'stream' => false,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'options' => [
                        'temperature' => 0.1,
                        'num_ctx' => 8192,
                        'num_predict' => 500,
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Smart Chatbot Ollama HTTP Error', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return 'Sorry, the AI assistant is not responding right now.';
            }

            $data = $response->json();

            return $data['message']['content'] ?? 'Sorry, I could not generate an answer.';
        } catch (Throwable $e) {
            Log::error('Smart Chatbot Ollama Connection Error', [
                'message' => $e->getMessage(),
            ]);

            return 'Sorry, the AI assistant is not connected right now.';
        }
    }
}