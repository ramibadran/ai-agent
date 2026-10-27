<?php

namespace Tests\Feature;

use App\Models\KnowledgeContent;
use App\Services\V1\QdrantClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KnowledgeContentIndexerTest extends TestCase
{
    public function test_replaces_existing_content_vectors_with_qdrant_points(): void
    {
        config()->set('services.qdrant.url', 'https://qdrant.test');
        config()->set('services.qdrant.collection', 'knowledge_chunks');
        config()->set('services.qdrant.vector_size', 3);
        Http::preventStrayRequests();
        Http::fake([
            'qdrant.test/collections/knowledge_chunks' => Http::sequence()->push([], 404)->push(['status' => 'ok']),
            'qdrant.test/collections/knowledge_chunks/points/delete*' => Http::response(['status' => 'ok']),
            'qdrant.test/collections/knowledge_chunks/points*' => Http::response(['status' => 'ok']),
        ]);
        $content = new KnowledgeContent([
            'title' => 'Refund policy',
            'body' => 'Refund details.',
            'status' => 'published',
            'version' => 7,
            'content_checksum' => str_repeat('a', 64),
        ]);
        $content->id = 45;

        app(QdrantClient::class)->replaceContentVectors($content, ['First chunk'], [[0.1, 0.2, 0.3]]);

        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_contains($request->url(), '/points/delete')
            && $request['filter']['must'][0]['match']['value'] === 45);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_contains($request->url(), '/points?wait=true')
            && $request['points'][0]['payload']['content_version'] === 7
            && $request['points'][0]['vector'] === [0.1, 0.2, 0.3]);
    }
}
