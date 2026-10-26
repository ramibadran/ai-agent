<?php

namespace Tests\Feature\Models;

use App\Models\ContentIndexRun;
use App\Models\KnowledgeContent;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ContentIndexRunTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_completed_index_run_belongs_to_its_content(): void
    {
        $content = KnowledgeContent::factory()->create([
            'version' => 3,
            'content_checksum' => str_repeat('a', 64),
        ]);

        $indexRun = ContentIndexRun::factory()
            ->for($content)
            ->completed()
            ->create([
                'content_version' => $content->version,
                'content_checksum' => $content->content_checksum,
            ]);

        $this->assertModelExists($indexRun);
        $this->assertSame('completed', $indexRun->status);
        $this->assertSame(3, $indexRun->content_version);
        $this->assertGreaterThan(0, $indexRun->vector_count);
        $this->assertTrue($indexRun->knowledgeContent->is($content));
    }

    public function test_deleting_content_removes_its_index_runs(): void
    {
        $content = KnowledgeContent::factory()->create();
        $indexRun = ContentIndexRun::factory()->for($content)->create();

        $content->delete();

        $this->assertDatabaseMissing('content_index_runs', ['id' => $indexRun->id]);
    }
}
