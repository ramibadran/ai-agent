<?php

namespace Tests\Feature;

use App\Contracts\EmbeddingProvider;
use App\Services\V1\KnowledgeRetriever;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class KnowledgeRetrieverTest extends TestCase
{
    public function test_it_embeds_a_question_and_returns_qdrant_results(): void
    {
        config()->set('services.qdrant.url', 'http://qdrant.test');
        config()->set('services.qdrant.collection', 'knowledge_chunks');
        config()->set('services.qdrant.vector_size', 3);

        $embeddingProvider = Mockery::mock(EmbeddingProvider::class);
        $embeddingProvider->shouldReceive('embed')
            ->once()
            ->with(['What was revenue in 2024?'])
            ->andReturn([[0.1, 0.2, 0.3]]);
        $this->app->instance(EmbeddingProvider::class, $embeddingProvider);

        Http::fake([
            'http://qdrant.test/collections/knowledge_chunks/points/query' => Http::response([
                'result' => [
                    'points' => [[
                        'id' => 'point-1',
                        'score' => 0.91,
                        'payload' => [
                            'content_id' => 7,
                            'chunk_number' => 2,
                            'title' => 'Northstar Nexus: Fictional Company Profile',
                            'source_url' => 'https://northstar-nexus.example/company-profile',
                            'text' => 'FY2024 revenue was 18.4 million USD.',
                        ],
                    ]],
                ],
            ]),
        ]);

        $results = app(KnowledgeRetriever::class)->retrieve('What was revenue in 2024?');

        $this->assertSame([
            [
                'point_id' => 'point-1',
                'content_id' => 7,
                'chunk_number' => 2,
                'title' => 'Northstar Nexus: Fictional Company Profile',
                'source_url' => 'https://northstar-nexus.example/company-profile',
                'text' => 'FY2024 revenue was 18.4 million USD.',
                'score' => 0.91,
            ],
        ], $results);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://qdrant.test/collections/knowledge_chunks/points/query'
                && $request['query'] === [0.1, 0.2, 0.3]
                && $request['limit'] === 5
                && $request['with_vector'] === false;
        });
    }
}
