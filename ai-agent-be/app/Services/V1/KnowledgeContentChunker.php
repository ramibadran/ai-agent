<?php

namespace App\Services\V1;

class KnowledgeContentChunker
{
    /**
     * Split content into bounded, overlapping word chunks.
     *
     * A tokenizer package can later replace this implementation without
     * changing the indexing workflow or Qdrant contract.
     *
     * @return array<int, string>
     */
    public function chunk(string $text): array
    {
        $cleanText = trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));

        if ($cleanText === '') {
            return [];
        }

        $words = preg_split('/\s+/u', $cleanText, flags: PREG_SPLIT_NO_EMPTY);
        $chunkSize = (int) config('services.qdrant.chunk_size');
        $overlap = (int) config('services.qdrant.chunk_overlap');

        if (! is_array($words) || $chunkSize < 1 || $overlap < 0 || $overlap >= $chunkSize) {
            throw new \InvalidArgumentException('Invalid knowledge-content chunk configuration.');
        }

        $chunks = [];
        $step = $chunkSize - $overlap;

        for ($offset = 0; $offset < count($words); $offset += $step) {
            $chunk = implode(' ', array_slice($words, $offset, $chunkSize));

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            if ($offset + $chunkSize >= count($words)) {
                break;
            }
        }

        return $chunks;
    }
}
