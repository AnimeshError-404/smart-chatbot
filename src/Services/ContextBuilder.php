<?php

namespace Ridgeben\SmartChatbot\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ContextBuilder
{
    public function build(Collection $results): string
    {
        if ($results->isEmpty()) {
            return '';
        }

        $context = $results->map(function ($result) {
            $source = $result['source'];
            $row = $result['row'];

            $lines = [];

            $lines[] = 'Source Type: ' . ($result['source_type'] ?? 'general');
            $lines[] = 'Title: ' . ($result['title'] ?? 'Untitled');

            foreach (($source['context_columns'] ?? []) as $column => $label) {
                if (isset($row->{$column}) && !empty($row->{$column})) {
                    $lines[] = $label . ': ' . $this->clean($row->{$column});
                }
            }

            return implode("\n", $lines);
        })->implode("\n\n---\n\n");

        return Str::limit(
            $context,
            config('smart-chatbot.context.max_characters', 12000)
        );
    }

    private function clean($value): string
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $value = strip_tags((string) $value);
        $value = html_entity_decode($value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }
}