<?php

namespace Tests\Feature;

use App\Contracts\AnswerGenerator;
use App\Models\User;
use App\Services\V1\ChatService;
use App\Services\V1\KnowledgeRetriever;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
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
        $answerGenerator->shouldReceive('streamGenerate')->once()->andReturn([
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

    public function test_it_returns_a_safe_answer_without_calling_the_llm_when_no_sources_exist(): void
    {
        $user = User::factory()->create();
        $retriever = Mockery::mock(KnowledgeRetriever::class);
        $retriever->shouldReceive('retrieve')->once()->andReturn([]);
        $this->app->instance(KnowledgeRetriever::class, $retriever);

        $answerGenerator = Mockery::mock(AnswerGenerator::class);
        $answerGenerator->shouldNotReceive('generate');
        $this->app->instance(AnswerGenerator::class, $answerGenerator);

        $result = app(ChatService::class)->createConversation($user, 'What is the weather today?');

        $this->assertSame('I could not find this information in the available knowledge base.', $result['assistantMessage']->content);
        $this->assertSame('completed', $result['assistantMessage']->status);
        $this->assertNull($result['assistantMessage']->model);
        $this->assertSame([], $result['assistantMessage']->retrieval_context);
    }

    public function test_it_saves_web_citations_without_querying_qdrant(): void
    {
        $user = User::factory()->create();
        $retriever = Mockery::mock(KnowledgeRetriever::class);
        $retriever->shouldNotReceive('retrieve');
        $this->app->instance(KnowledgeRetriever::class, $retriever);

        $answerGenerator = Mockery::mock(AnswerGenerator::class);
        $answerGenerator->shouldReceive('streamWebGenerate')->once()->andReturn([
            'answer' => 'A public web answer with a cited source.',
            'model' => 'gpt-5.4-mini', 'prompt_tokens' => 1000, 'completion_tokens' => 20,
            'total_tokens' => 1020, 'latency_ms' => 500, 'cost_usd' => '0.00084000',
            'sources' => [[
                'source_type' => 'web',
                'title' => 'Example public source',
                'source_url' => 'https://example.com/article',
            ]],
        ]);
        $this->app->instance(AnswerGenerator::class, $answerGenerator);

        $result = app(ChatService::class)->createConversation($user, 'What happened today?', 'web');

        $this->assertSame('A public web answer with a cited source.', $result['assistantMessage']->content);
        $this->assertSame('web', $result['assistantMessage']->retrieval_context[0]['source_type']);
        $this->assertSame('https://example.com/article', $result['assistantMessage']->retrieval_context[0]['source_url']);
    }

    public function test_an_authenticated_user_can_create_and_view_a_conversation(): void
    {
        $user = User::factory()->create();
        $retriever = Mockery::mock(KnowledgeRetriever::class);
        $retriever->shouldReceive('retrieve')->once()->andReturn([[
            'point_id' => 'point-1', 'content_id' => 7, 'chunk_number' => 2,
            'title' => 'Northstar Nexus: Fictional Company Profile', 'source_url' => null,
            'text' => 'FY2024 revenue was 18.4 million USD.', 'score' => 0.91,
        ]]);
        $this->app->instance(KnowledgeRetriever::class, $retriever);

        $answerGenerator = Mockery::mock(AnswerGenerator::class);
        $answerGenerator->shouldReceive('streamGenerate')->once()->andReturn([
            'answer' => 'Northstar Nexus reported 18.4 million USD in FY2024.',
            'model' => 'gpt-5.4-mini', 'prompt_tokens' => 1000, 'completion_tokens' => 20,
            'total_tokens' => 1020, 'latency_ms' => 500, 'cost_usd' => '0.00084000',
        ]);
        $this->app->instance(AnswerGenerator::class, $answerGenerator);

        $headers = ['Authorization' => 'Bearer '.JWTAuth::fromUser($user)];
        $response = $this->postJson('/api/v1/chat/conversations', [
            'message' => 'What was revenue in 2024?',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.messages.1.content', 'Northstar Nexus reported 18.4 million USD in FY2024.');

        $conversationId = $response->json('data.id');

        $this->getJson("/api/v1/chat/conversations/{$conversationId}", $headers)
            ->assertOk()
            ->assertJsonCount(2, 'data.messages');
    }
}
