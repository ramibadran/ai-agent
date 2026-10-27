<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\ContentIndexRun;
use App\Models\KnowledgeContent;
use App\Models\User;
use App\Services\V1\KnowledgeContentIndexer;
use App\Services\V1\QdrantClient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class KnowledgeContentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_content_and_a_pending_index_run(): void
    {
        $indexer = Mockery::mock(KnowledgeContentIndexer::class);
        $indexer->shouldReceive('index')->once()->with(Mockery::type(ContentIndexRun::class));
        $this->app->instance(KnowledgeContentIndexer::class, $indexer);
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->withToken(JWTAuth::fromUser($admin))->postJson('/api/v1/admin/knowledge-contents', [
            'title' => 'Support policy',
            'body' => 'This article explains the support policy.',
            'metadata' => ['tags' => ['support']],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Support policy')
            ->assertJsonPath('data.status', 'published');
        $this->assertDatabaseHas('knowledge_contents', ['title' => 'Support policy', 'version' => 1]);
        $this->assertDatabaseHas('content_index_runs', ['status' => 'pending', 'content_version' => 1]);
    }

    public function test_non_admin_cannot_list_content(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        KnowledgeContent::factory()->create();

        $this->withToken(JWTAuth::fromUser($user))->getJson('/api/v1/admin/knowledge-contents')->assertForbidden();
    }

    public function test_admin_update_indexes_content_directly(): void
    {
        $indexer = Mockery::mock(KnowledgeContentIndexer::class);
        $indexer->shouldReceive('index')->once()->with(Mockery::type(ContentIndexRun::class));
        $this->app->instance(KnowledgeContentIndexer::class, $indexer);
        $admin = User::factory()->create(['is_admin' => true]);
        $content = KnowledgeContent::factory()->for($admin, 'createdBy')->create();

        $response = $this->withToken(JWTAuth::fromUser($admin))->putJson('/api/v1/admin/knowledge-contents/'.$content->id, [
            'title' => 'Updated support policy',
        ]);

        $response->assertOk()->assertJsonPath('data.title', 'Updated support policy');
        $this->assertDatabaseHas('knowledge_contents', ['id' => $content->id, 'version' => 2]);
    }

    public function test_admin_delete_removes_vectors_directly_before_removing_content(): void
    {
        $qdrant = Mockery::mock(QdrantClient::class);
        $qdrant->shouldReceive('deleteContentVectors')->once();
        $this->app->instance(QdrantClient::class, $qdrant);
        $admin = User::factory()->create(['is_admin' => true]);
        $content = KnowledgeContent::factory()->for($admin, 'createdBy')->create();

        $response = $this->withToken(JWTAuth::fromUser($admin))->deleteJson('/api/v1/admin/knowledge-contents/'.$content->id);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertModelMissing($content);
    }
}
