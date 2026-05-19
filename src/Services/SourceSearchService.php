<?php

namespace Ridgeben\SmartChatbot\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SourceSearchService
{
    public function search(string $question): Collection
    {
        $sources = config('smart-chatbot.sources', []);
        $allResults = collect();

        foreach ($sources as $source) {
            $results = $this->searchGenericSource($source, $question);
            $allResults = $allResults->merge($results);
        }

        return $allResults
            ->sortByDesc(fn ($item) => $item['meta']['score'] ?? 0)
            ->take(config('smart-chatbot.context.max_results', 5))
            ->values();
    }

    public function findFromMemory(array $entity): Collection
    {
        $source = $entity['source'] ?? null;
        $id = $entity['id'] ?? null;
        $primaryKey = $entity['primary_key'] ?? 'id';

        if (!$source || !$id || empty($source['table'])) {
            return collect();
        }

        $query = DB::table($source['table'])->where($primaryKey, $id);

        if (!empty($source['active_column']) && array_key_exists('active_value', $source)) {
            $query->where($source['active_column'], $source['active_value']);
        }

        $row = $query->first();

        if (!$row) {
            return collect();
        }

        $titleColumn = $source['title_column'] ?? null;

        return collect([
            [
                'source_type' => $source['source_type'] ?? 'general',
                'source' => $source,
                'title' => $titleColumn && isset($row->{$titleColumn})
                    ? $row->{$titleColumn}
                    : ($entity['title'] ?? 'Untitled'),
                'row' => $row,
                'meta' => [
                    'result_reason' => 'memory_followup',
                    'score' => 100,
                ],
            ],
        ]);
    }

    private function searchGenericSource(array $source, string $question): Collection
    {
        $table = $source['table'] ?? null;
        $searchableColumns = $source['searchable_columns'] ?? [];
        $titleColumn = $source['title_column'] ?? null;

        if (!$table || !$titleColumn || empty($searchableColumns)) {
            return collect();
        }

        $keywords = $this->extractKeywords($question);

        if (empty($keywords)) {
            return collect();
        }

        $rows = DB::table($table)
            ->when(
                !empty($source['active_column']) && array_key_exists('active_value', $source),
                fn ($query) => $query->where($source['active_column'], $source['active_value'])
            )
            ->limit(300)
            ->get();

        $scored = collect();

        foreach ($rows as $row) {
            $score = 0;

            $titleText = $this->normalizeText((string) ($row->{$titleColumn} ?? ''));

            $combinedText = '';

            foreach ($searchableColumns as $column) {
                if (isset($row->{$column}) && !empty($row->{$column})) {
                    $combinedText .= ' ' . $this->normalizeText((string) $row->{$column});
                }
            }

            foreach ($keywords as $keyword) {
                if ($titleText && str_contains($titleText, $keyword)) {
                    $score += 30;
                }

                if ($combinedText && str_contains($combinedText, $keyword)) {
                    $score += 8;
                }
            }

            if ($score > 0) {
                $scored->push([
                    'source_type' => $source['source_type'] ?? 'general',
                    'source' => $source,
                    'title' => $row->{$titleColumn} ?? 'Untitled',
                    'row' => $row,
                    'meta' => [
                        'result_reason' => 'generic_database_search',
                        'score' => $score,
                    ],
                ]);
            }
        }

        return $scored;
    }

    private function extractKeywords(string $text): array
    {
        $stopWords = [
            'what', 'which', 'where', 'when', 'who', 'how',
            'the', 'is', 'are', 'am', 'do', 'does', 'did',
            'you', 'your', 'have', 'has', 'about', 'tell',
            'me', 'please', 'details', 'information', 'info',
            'give', 'can', 'could', 'would', 'from', 'with',
            'and', 'for', 'this', 'that', 'there', 'any',
            'all', 'it', 'its', 'some', 'suggest', 'of',
            'our', 'their', 'a', 'an', 'to', 'in', 'on',
        ];

        return collect(preg_split('/[\s,?!.:;()\-]+/', strtolower($text)))
            ->map(fn ($word) => trim($word))
            ->filter(fn ($word) => strlen($word) >= 3)
            ->reject(fn ($word) => in_array($word, $stopWords))
            ->unique()
            ->take(15)
            ->values()
            ->toArray();
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower($text);
        $text = str_replace(['-', '_', '/', '\\'], ' ', $text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}