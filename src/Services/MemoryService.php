<?php

namespace Ridgeben\SmartChatbot\Services;

use Illuminate\Support\Str;

class MemoryService
{
    public function isResetRequest(string $question): bool
    {
        $text = strtolower(trim($question));

        return in_array($text, [
            'clear memory',
            'reset memory',
            'new chat',
            'start again',
        ]);
    }

    public function clear(): void
    {
        session()->forget([
            'smart_chatbot.history',
            'smart_chatbot.last_entity',
        ]);
    }

    public function save(string $question, string $answer): void
    {
        $history = session('smart_chatbot.history', []);

        $history[] = [
            'user' => Str::limit($question, 500),
            'assistant' => Str::limit($answer, 1000),
            'time' => now()->toDateTimeString(),
        ];

        session([
            'smart_chatbot.history' => array_slice(
                $history,
                -config('smart-chatbot.memory.history_limit', 6)
            ),
        ]);
    }

    public function rememberEntity(array $result): void
    {
        $row = $result['row'];
        $source = $result['source'];
        $primaryKey = $source['primary_key'] ?? 'id';

        if (!isset($row->{$primaryKey})) {
            return;
        }

        session([
            'smart_chatbot.last_entity' => [
                'source_type' => $result['source_type'] ?? null,
                'title' => $result['title'] ?? null,
                'id' => $row->{$primaryKey},
                'primary_key' => $primaryKey,
                'source' => $source,
            ],
        ]);
    }

    public function lastEntity(): ?array
    {
        return session('smart_chatbot.last_entity');
    }

    public function hasPronounReference(string $question): bool
    {
        $text = strtolower(trim($question));

        return str_contains($text, ' it ')
            || str_starts_with($text, 'it ')
            || str_contains($text, ' this ')
            || str_starts_with($text, 'this ')
            || str_contains($text, ' that ')
            || str_starts_with($text, 'that ')
            || str_contains($text, 'same one')
            || str_contains($text, 'same product')
            || str_contains($text, 'same project')
            || str_contains($text, 'same service');
    }

    public function looksLikeFollowUp(string $question): bool
    {
        $text = strtolower(trim($question));

        $followUpWords = [
            'completed',
            'complete',
            'ongoing',
            'upcoming',
            'status',
            'location',
            'located',
            'facility',
            'facilities',
            'feature',
            'features',
            'price',
            'size',
            'parking',
            'stock',
            'available',
            'availability',
            'warranty',
            'address',
            'contact',
            'phone',
            'email',
        ];

        foreach ($followUpWords as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        return false;
    }

    public function historyText(): string
    {
        $history = session('smart_chatbot.history', []);

        if (empty($history)) {
            return 'No previous conversation.';
        }

        return collect($history)
            ->map(function ($item) {
                return "User: {$item['user']}\nAssistant: {$item['assistant']}";
            })
            ->implode("\n\n");
    }
}