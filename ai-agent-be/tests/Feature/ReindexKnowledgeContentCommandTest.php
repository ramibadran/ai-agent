<?php

namespace Tests\Feature;

use App\Models\KnowledgeContent;
use App\Services\V1\KnowledgeContentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Tests\TestCase;

class ReindexKnowledgeContentCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reindexes_all_published_content(): void
    {
        $published = KnowledgeContent::factory()->published()->create();
        KnowledgeContent::factory()->create(['status' => 'draft']);
        $service = Mockery::mock(KnowledgeContentService::class);
        $service->shouldReceive('reindex')->once()->with(Mockery::on(fn (KnowledgeContent $content): bool => $content->is($published)));
        $this->app->instance(KnowledgeContentService::class, $service);

        $this->artisan('knowledge:reindex')
            ->expectsOutput("Indexed [{$published->id}] {$published->title}")
            ->assertExitCode(0);
    }
}
