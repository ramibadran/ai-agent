<?php

namespace Tests\Feature;

use App\Services\V1\KnowledgeContentChunker;
use Tests\TestCase;

class KnowledgeContentIndexJobTest extends TestCase
{
    public function test_chunker_keeps_the_configured_overlap_between_chunks(): void
    {
        config()->set('services.qdrant.chunk_size', 4);
        config()->set('services.qdrant.chunk_overlap', 2);

        $chunks = app(KnowledgeContentChunker::class)->chunk('one two three four five six seven');

        $this->assertSame(['one two three four', 'three four five six', 'five six seven'], $chunks);
    }
}
