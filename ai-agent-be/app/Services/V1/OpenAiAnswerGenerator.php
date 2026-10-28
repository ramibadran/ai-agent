<?php

namespace App\Services\V1;

use App\Contracts\AnswerGenerator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class OpenAiAnswerGenerator implements AnswerGenerator
{
    /**
     * @param  list<array{point_id: string|int, content_id: int, chunk_number: int, title: string, source_url: string|null, text: string, score: float}>  $context
     * @param  list<array{role: string, content: string}>  $history
     * @return array{answer: string, model: string, prompt_tokens: int|null, completion_tokens: int|null, total_tokens: int|null, latency_ms: int, cost_usd: string}
     */
    public function generate(string $question, array $context, array $history = []): array
    {
        if ($context === []) {
            throw new InvalidArgumentException('Knowledge context is required to generate an answer.');
        }

        $startedAt = hrtime(true);
        $response = $this->client()->post('/responses', [
            'model' => $this->model(),
            'instructions' => 'You are a helpful knowledge assistant. Answer only from the supplied knowledge context. If the context does not contain the answer, clearly say that you do not have enough information. Do not invent facts. Keep the answer concise and use plain text.',
            'input' => $this->formatInput($question, $context, $history),
            'max_output_tokens' => (int) config('services.openai.chat_max_output_tokens'),
        ])->throw();
        $latencyMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        $answer = trim((string) $response->json('output_text'));

        if ($answer === '') {
            throw new RuntimeException('The answer provider returned no text.');
        }

        $promptTokens = $this->nullableInteger($response->json('usage.input_tokens'));
        $completionTokens = $this->nullableInteger($response->json('usage.output_tokens'));

        return [
            'answer' => $answer,
            'model' => (string) $response->json('model', $this->model()),
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $this->nullableInteger($response->json('usage.total_tokens')),
            'latency_ms' => $latencyMs,
            'cost_usd' => $this->calculateCost($promptTokens, $completionTokens),
        ];
    }

    private function client(): PendingRequest
    {
        $apiKey = (string) config('services.openai.api_key');

        if ($apiKey === '') {
            throw new InvalidArgumentException('OPENAI_API_KEY is required to generate answers.');
        }

        return Http::baseUrl((string) config('services.openai.base_url'))
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(90);
    }

    /**
     * @param  list<array{point_id: string|int, content_id: int, chunk_number: int, title: string, source_url: string|null, text: string, score: float}>  $context
     */
    private function formatContext(array $context): string
    {
        return collect($context)
            ->map(fn (array $chunk): string => "[Source {$chunk['content_id']}, chunk {$chunk['chunk_number']}: {$chunk['title']}]\n{$chunk['text']}")
            ->implode("\n\n");
    }

    /**
     * @param  list<array{point_id: string|int, content_id: int, chunk_number: int, title: string, source_url: string|null, text: string, score: float}>  $context
     * @param  list<array{role: string, content: string}>  $history
     */
    private function formatInput(string $question, array $context, array $history): string
    {
        $historyText = $history === []
            ? 'No previous messages.'
            : collect($history)
                ->map(fn (array $message): string => ucfirst($message['role']).": {$message['content']}")
                ->implode("\n");

        return "Conversation history:\n{$historyText}\n\nQuestion:\n{$question}\n\nKnowledge context:\n".$this->formatContext($context);
    }

    private function model(): string
    {
        return (string) config('services.openai.chat_model');
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function calculateCost(?int $promptTokens, ?int $completionTokens): string
    {
        $inputCost = (($promptTokens ?? 0) / 1_000_000) * (float) config('services.openai.chat_input_cost_per_million');
        $outputCost = (($completionTokens ?? 0) / 1_000_000) * (float) config('services.openai.chat_output_cost_per_million');

        return number_format($inputCost + $outputCost, 8, '.', '');
    }
}
