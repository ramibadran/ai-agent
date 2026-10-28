<?php

namespace Tests\Feature;

use App\Services\V1\OpenAiAnswerGenerator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiAnswerGeneratorTest extends TestCase
{
    public function test_it_generates_a_grounded_answer_and_returns_usage(): void
    {
        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.base_url', 'http://openai.test/v1');
        config()->set('services.openai.chat_model', 'gpt-5.4-mini');
        config()->set('services.openai.chat_input_cost_per_million', 0.75);
        config()->set('services.openai.chat_output_cost_per_million', 4.50);

        Http::fake([
            'http://openai.test/v1/responses' => Http::response([
                'model' => 'gpt-5.4-mini',
                'output_text' => 'Northstar Nexus reported 18.4 million USD in FY2024 revenue.',
                'usage' => [
                    'input_tokens' => 1000,
                    'output_tokens' => 20,
                    'total_tokens' => 1020,
                ],
            ]),
        ]);

        $result = app(OpenAiAnswerGenerator::class)->generate('What was revenue in 2024?', [[
            'point_id' => 'point-1',
            'content_id' => 7,
            'chunk_number' => 2,
            'title' => 'Northstar Nexus: Fictional Company Profile',
            'source_url' => null,
            'text' => 'FY2024 revenue was 18.4 million USD.',
            'score' => 0.91,
        ]]);

        $this->assertSame('Northstar Nexus reported 18.4 million USD in FY2024 revenue.', $result['answer']);
        $this->assertSame('gpt-5.4-mini', $result['model']);
        $this->assertSame(1000, $result['prompt_tokens']);
        $this->assertSame(20, $result['completion_tokens']);
        $this->assertSame(1020, $result['total_tokens']);
        $this->assertSame('0.00084000', $result['cost_usd']);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://openai.test/v1/responses'
            && $request['model'] === 'gpt-5.4-mini'
            && str_contains($request['input'], 'FY2024 revenue was 18.4 million USD.'));
    }
}
