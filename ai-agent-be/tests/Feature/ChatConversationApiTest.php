<?php

namespace Tests\Feature;

use App\Contracts\AnswerGenerator;
use App\Models\User;
use App\Services\V1\ChatService;
use App\Services\V1\KnowledgeRetriever;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Tests\TestCase;

class ChatConversationApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_saves_a_complete_grounded_chat_exchange(): void
    {
        $user = User::factory()->create();
        $retriever = Mockery::mock(KnowledgeRetriever::class);
        $retriever->shouldReceive('retrieve')->once()->with('What was revenue in 2024?')->andReturn([[
            'point_id' => 'point-1', 'content_id' => 7, 'chunk_number' => 2,
            'title' => 'Northstar Nexus: Fictional Company Profile', 'source_url' => null,
            'text' => 'FY2024 revenue was 18.4 million USD.', 'score' => 0.91,
        ]]);
        $this->app->instance(KnowledgeRetriever::class, $retriever);

        $answerGenerator = Mockery::mock(AnswerGenerator::class);
        $answerGenerator->shouldReceive('generate')->once()->andReturn([
            'answer' => 'Northstar Nexus reported 18.4 million USD in FY2024.',
            'model' => 'gpt-5.4-mini', 'prompt_tokens' => 1000, 'completion_tokens' => 20,
            'total_tokens' => 1020, 'latency_ms' => 500, 'cost_usd' => '0.00084000',
        ]);
        $this->app->instance(AnswerGenerator::class, $answerGenerator);

        $result = app(ChatService::class)->createConversation($user, 'What was revenue in 2024?');

        $this->assertSame('What was revenue in 2024?', $result['conversation']->title);
        $this->assertSame('user', $result['userMessage']->role);
        $this->assertSame('assistant', $result['assistantMessage']->role);
        $this->assertSame('gpt-5.4-mini', $result['assistantMessage']->model);
        $this->assertSame(1020, $result['assistantMessage']->total_tokens);
        $this->assertSame('0.00084000', $result['assistantMessage']->cost_usd);
        $this->assertCount(2, $result['conversation']->messages);
        $this->assertNotNull($result['conversation']->fresh()->last_message_at);
    }

    public function test_chat_routes_are_registered(): void
    {
        $this->getJson('/api/v1/chat/conversations')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Authorization header is missing.');
    }
}
