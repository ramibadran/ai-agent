<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\KnowledgeContent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class KnowledgeContentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_content_and_a_pending_index_run(): void
    {
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
}
