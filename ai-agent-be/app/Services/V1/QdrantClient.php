<?php

namespace App\Services\V1;

use App\Models\KnowledgeContent;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class QdrantClient
{
    /**
     * @param  array<int, string>  $chunks
     * @param  array<int, array<int, float>>  $vectors
     */
    public function replaceContentVectors(KnowledgeContent $content, array $chunks, array $vectors): void
    {
        if (count($chunks) !== count($vectors)) {
            throw new \InvalidArgumentException('Every content chunk must have an embedding vector.');
        }

        $this->ensureCollection();
        $this->deleteContentVectors($content->id);

        $points = collect($chunks)->map(function (string $chunk, int $index) use ($content, $vectors): array {
            return [
                'id' => $this->pointId($content->id, $content->version, $index),
                'vector' => $vectors[$index],
                'payload' => [
                    'content_id' => $content->id,
                    'content_version' => $content->version,
                    'chunk_number' => $index + 1,
                    'title' => $content->title,
                    'source_url' => $content->source_url,
                    'status' => $content->status,
                    'checksum' => $content->content_checksum,
                    'text' => $chunk,
                ],
            ];
        })->all();

        if ($points === []) {
            return;
        }

        $this->client()->put($this->collectionPath().'/points?wait=true', ['points' => $points])->throw();
    }

    public function deleteContentVectors(int $contentId): void
    {
        $response = $this->client()->post($this->collectionPath().'/points/delete?wait=true', [
            'filter' => [
                'must' => [[
                    'key' => 'content_id',
                    'match' => ['value' => $contentId],
                ]],
            ],
        ]);

        if ($response->notFound()) {
            return;
        }

        $response->throw();
    }

    private function ensureCollection(): void
    {
        $response = $this->client()->get($this->collectionPath());

        if ($response->successful()) {
            return;
        }

        if (! $response->notFound()) {
            $response->throw();
        }

        $this->client()->put($this->collectionPath(), [
            'vectors' => [
                'size' => (int) config('services.qdrant.vector_size'),
                'distance' => 'Cosine',
            ],
        ])->throw();
    }

    private function client(): PendingRequest
    {
        $client = Http::baseUrl(rtrim((string) config('services.qdrant.url'), '/'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout(30);

        $apiKey = (string) config('services.qdrant.api_key');

        return $apiKey === '' ? $client : $client->withHeader('api-key', $apiKey);
    }

    private function collectionPath(): string
    {
        return '/collections/'.rawurlencode((string) config('services.qdrant.collection'));
    }

    private function pointId(int $contentId, int $version, int $chunkIndex): string
    {
        $hash = sha1("content:{$contentId}:version:{$version}:chunk:{$chunkIndex}", true);
        $bytes = array_values(unpack('C16', substr($hash, 0, 16)));
        $bytes[6] = ($bytes[6] & 0x0F) | 0x50;
        $bytes[8] = ($bytes[8] & 0x3F) | 0x80;
        $hex = implode('', array_map(static fn (int $byte): string => sprintf('%02x', $byte), $bytes));

        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20);
    }
}
