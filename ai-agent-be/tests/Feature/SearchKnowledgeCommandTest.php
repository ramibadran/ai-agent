<?php

namespace Tests\Feature;

use App\Services\V1\KnowledgeRetriever;
use Mockery;
use Tests\TestCase;

class SearchKnowledgeCommandTest extends TestCase
{
    public function test_it_displays_retrieval_results(): void
    {
        $retriever = Mockery::mock(KnowledgeRetriever::class);
        $retriever->shouldReceive('retrieve')
            ->once()
            ->with('What was revenue in 2024?', 5)
            ->andReturn([[
                'point_id' => 'point-1',
                'content_id' => 7,
                'chunk_number' => 2,
                'title' => 'Northstar Nexus: Fictional Company Profile',
                'source_url' => null,
                'text' => 'FY2024 revenue was 18.4 million USD.',
                'score' => 0.91,
            ]]);
        $this->app->instance(KnowledgeRetriever::class, $retriever);

        $this->artisan('knowledge:search', ['question' => 'What was revenue in 2024?'])
            ->expectsTable(
                ['Score', 'Content', 'Chunk', 'Text'],
                [['0.9100', 'Northstar Nexus: Fictional Company Profile', 2, 'FY2024 revenue was 18.4 million USD.']],
            )
            ->assertExitCode(0);
    }
}
