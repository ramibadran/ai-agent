<?php

namespace Tests\Feature\Models;

use App\Models\KnowledgeContent;
use App\Models\User;
use Database\Seeders\KnowledgeContentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class KnowledgeContentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_published_content_preserves_its_creator_and_metadata(): void
    {
        $creator = User::factory()->create();

        $content = KnowledgeContent::factory()
            ->for($creator, 'createdBy')
            ->published()
            ->create([
                'metadata' => [
                    'locale' => 'en',
                    'tags' => ['refunds', 'policy'],
                ],
            ]);

        $this->assertModelExists($content);
        $this->assertSame('published', $content->status);
        $this->assertSame(['locale' => 'en', 'tags' => ['refunds', 'policy']], $content->metadata);
        $this->assertNotNull($content->published_at);
        $this->assertTrue($content->createdBy->is($creator));
    }

    public function test_demo_content_seeder_creates_repeatable_published_articles(): void
    {
        $this->seed(KnowledgeContentSeeder::class);
        $this->seed(KnowledgeContentSeeder::class);

        $this->assertSame(6, KnowledgeContent::query()->count());
        $this->assertDatabaseHas('users', ['email' => 'admin@example.test']);
        $this->assertDatabaseHas('knowledge_contents', [
            'slug' => 'refund-and-returns-policy',
            'status' => 'published',
        ]);
    }
}
