<?php

namespace App\Services\V1;

use App\Contracts\EmbeddingProvider;
use InvalidArgumentException;

class KnowledgeRetriever
{
    public function __construct(private readonly EmbeddingProvider $embeddingProvider, private readonly QdrantClient $qdrant) {}

    /**
     * Retrieve the most semantically relevant published knowledge chunks.
     *
     * @return list<array{point_id: string|int, content_id: int, chunk_number: int, title: string, source_url: string|null, text: string, score: float}>
     */
    public function retrieve(string $question, ?int $limit = null): array
    {
        $question = trim($question);

        if ($question === '') {
            throw new InvalidArgumentException('A question is required for knowledge retrieval.');
        }

        $limit ??= (int) config('services.qdrant.retrieval_limit');

        if ($limit < 1) {
            throw new InvalidArgumentException('The retrieval limit must be at least one.');
        }

        $vector = $this->embeddingProvider->embed([$question])[0];

        return collect($this->qdrant->search($vector, $limit))
            ->map(function (array $point): array {
                $payload = $point['payload'];

                return [
                    'point_id' => $point['id'],
                    'content_id' => (int) $payload['content_id'],
                    'chunk_number' => (int) $payload['chunk_number'],
                    'title' => (string) $payload['title'],
                    'source_url' => filled($payload['source_url'] ?? null) ? (string) $payload['source_url'] : null,
                    'text' => (string) $payload['text'],
                    'score' => $point['score'],
                ];
            })
            ->all();
    }
}
