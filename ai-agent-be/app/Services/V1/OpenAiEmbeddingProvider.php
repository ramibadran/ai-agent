<?php

namespace App\Services\V1;

use App\Contracts\EmbeddingProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class OpenAiEmbeddingProvider implements EmbeddingProvider
{
    /**
     * @param  array<int, string>  $inputs
     * @return array<int, array<int, float>>
     */
    public function embed(array $inputs): array
    {
        if ($inputs === []) {
            return [];
        }

        $response = $this->client()->post('/embeddings', [
            'model' => config('services.openai.embedding_model'),
            'input' => $inputs,
            'encoding_format' => 'float',
            'dimensions' => $this->dimensions(),
        ])->throw();

        $embeddings = collect($response->json('data', []))
            ->sortBy('index')
            ->map(function (array $item): array {
                $embedding = $item['embedding'] ?? null;

                if (! is_array($embedding) || count($embedding) !== $this->dimensions()) {
                    throw new RuntimeException('The embedding provider returned an invalid vector dimension.');
                }

                return array_map(static fn (mixed $value): float => (float) $value, $embedding);
            })
            ->values()
            ->all();

        if (count($embeddings) !== count($inputs)) {
            throw new RuntimeException('The embedding provider returned an unexpected number of vectors.');
        }

        return $embeddings;
    }

    public function dimensions(): int
    {
        return (int) config('services.openai.embedding_dimensions');
    }

    private function client(): PendingRequest
    {
        $apiKey = (string) config('services.openai.api_key');

        if ($apiKey === '') {
            throw new InvalidArgumentException('OPENAI_API_KEY is required to create embeddings.');
        }

        return Http::baseUrl((string) config('services.openai.base_url'))
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(45);
    }
}
