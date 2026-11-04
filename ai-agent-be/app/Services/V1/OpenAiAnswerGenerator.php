<?php

namespace App\Services\V1;

use App\Contracts\AnswerGenerator;
use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class OpenAiAnswerGenerator implements AnswerGenerator
{
    /**
     * Stream OpenAI text deltas to the supplied callback while building the final answer.
     *
     * @param  list<array<string, mixed>>  $context
     * @param  list<array{role: string, content: string}>  $history
     * @param  Closure(string): void  $onDelta
     * @return array{answer: string, model: string, prompt_tokens: int|null, completion_tokens: int|null, total_tokens: int|null, latency_ms: int, cost_usd: string}
     */
    public function streamGenerate(string $question, array $context, array $history, Closure $onDelta): array
    {
        return $this->streamResponse($this->requestPayload($question, $context, $history), $onDelta);
    }

    /**
     * Stream a public-web answer and retain the URL citations returned by OpenAI.
     *
     * @param  list<array{role: string, content: string}>  $history
     * @param  Closure(string): void  $onDelta
     * @return array{answer: string, model: string, prompt_tokens: int|null, completion_tokens: int|null, total_tokens: int|null, latency_ms: int, cost_usd: string, sources: list<array{source_type: 'web', title: string, source_url: string}>}
     */
    public function streamWebGenerate(string $question, array $history, Closure $onDelta): array
    {
        return $this->streamResponse($this->webSearchRequestPayload($question, $history), $onDelta, true);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  Closure(string): void  $onDelta
     * @return array{answer: string, model: string, prompt_tokens: int|null, completion_tokens: int|null, total_tokens: int|null, latency_ms: int, cost_usd: string, sources?: list<array{source_type: 'web', title: string, source_url: string}>}
     */
    private function streamResponse(array $payload, Closure $onDelta, bool $includeWebSources = false): array
    {
        $startedAt = hrtime(true);
        $response = $this->streamClient()->post('responses', [
            'json' => $payload + ['stream' => true],
            'stream' => true,
        ]);
        $buffer = '';
        $answer = '';
        $completed = [];
        $body = $response->getBody();

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($position = strpos($buffer, "\n\n")) !== false) {
                $event = substr($buffer, 0, $position);
                $buffer = substr($buffer, $position + 2);
                $data = collect(explode("\n", $event))
                    ->filter(fn (string $line): bool => str_starts_with($line, 'data:'))
                    ->map(fn (string $line): string => trim(substr($line, 5)))
                    ->implode("\n");
                $payload = json_decode($data, true);

                if (! is_array($payload)) {
                    continue;
                }

                if (($payload['type'] ?? null) === 'response.output_text.delta' && is_string($payload['delta'] ?? null)) {
                    $answer .= $payload['delta'];
                    $onDelta($payload['delta']);
                }

                if (($payload['type'] ?? null) === 'response.completed' && is_array($payload['response'] ?? null)) {
                    $completed = $payload['response'];
                }
            }
        }

        $answer = trim($answer);

        if ($answer === '') {
            throw new RuntimeException('The answer provider returned no text.');
        }

        $promptTokens = $this->nullableInteger($completed['usage']['input_tokens'] ?? null);
        $completionTokens = $this->nullableInteger($completed['usage']['output_tokens'] ?? null);

        $result = [
            'answer' => $answer,
            'model' => (string) ($completed['model'] ?? $this->model()),
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $this->nullableInteger($completed['usage']['total_tokens'] ?? null),
            'latency_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'cost_usd' => $this->calculateCost($promptTokens, $completionTokens),
        ];

        if ($includeWebSources) {
            $result['sources'] = $this->webSources($completed);
        }

        return $result;
    }

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
        $response = $this->client()->post('/responses', $this->requestPayload($question, $context, $history))->throw();
        $latencyMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        $answer = $this->responseText($response->json());

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

    private function streamClient(): Client
    {
        $apiKey = (string) config('services.openai.api_key');

        if ($apiKey === '') {
            throw new InvalidArgumentException('OPENAI_API_KEY is required to generate answers.');
        }

        return new Client([
            'base_uri' => rtrim((string) config('services.openai.base_url'), '/').'/',
            'headers' => ['Authorization' => "Bearer {$apiKey}", 'Accept' => 'text/event-stream'],
            'connect_timeout' => 5,
            'timeout' => 90,
        ]);
    }

    /** @param list<array<string, mixed>> $context @param list<array{role: string, content: string}> $history @return array<string, mixed> */
    private function requestPayload(string $question, array $context, array $history): array
    {
        return [
            'model' => $this->model(),
            'instructions' => 'You are a helpful knowledge assistant. Answer only from the supplied knowledge context. If the context does not contain the answer, clearly say that you do not have enough information. Do not invent facts. Match the requested level of detail: keep ordinary answers concise, but when the user explicitly asks for a detailed, comprehensive, or long answer, cover the relevant supplied facts thoroughly with clear plain-text headings and bullet points.',
            'input' => $this->formatInput($question, $context, $history),
            'max_output_tokens' => (int) config('services.openai.chat_max_output_tokens'),
        ];
    }

    /** @param list<array{role: string, content: string}> $history @return array<string, mixed> */
    private function webSearchRequestPayload(string $question, array $history): array
    {
        return [
            'model' => $this->model(),
            'instructions' => 'You are a helpful web research assistant. Answer from current public web-search results only. Do not claim that public-web facts come from the internal knowledge base. Do not invent facts, URLs, or citations. Use clear plain text and make factual claims traceable to the returned web citations.',
            'input' => $this->formatWebInput($question, $history),
            'tools' => [['type' => 'web_search']],
            'max_output_tokens' => (int) config('services.openai.chat_max_output_tokens'),
        ];
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

    /** @param list<array{role: string, content: string}> $history */
    private function formatWebInput(string $question, array $history): string
    {
        $historyText = $history === []
            ? 'No previous messages.'
            : collect($history)
                ->map(fn (array $message): string => ucfirst($message['role']).": {$message['content']}")
                ->implode("\n");

        return "Conversation history:\n{$historyText}\n\nQuestion:\n{$question}";
    }

    /** @return list<array{source_type: 'web', title: string, source_url: string}> */
    private function webSources(array $response): array
    {
        return collect($response['output'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && ($item['type'] ?? null) === 'message')
            ->flatMap(fn (array $item) => $item['content'] ?? [])
            ->filter(fn (mixed $content): bool => is_array($content) && ($content['type'] ?? null) === 'output_text')
            ->flatMap(fn (array $content) => $content['annotations'] ?? [])
            ->filter(fn (mixed $annotation): bool => is_array($annotation) && ($annotation['type'] ?? null) === 'url_citation' && filled($annotation['url'] ?? null))
            ->map(fn (array $annotation): array => [
                'source_type' => 'web',
                'title' => filled($annotation['title'] ?? null) ? (string) $annotation['title'] : (string) $annotation['url'],
                'source_url' => (string) $annotation['url'],
            ])
            ->unique('source_url')
            ->values()
            ->all();
    }

    private function model(): string
    {
        return (string) config('services.openai.chat_model');
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Extract generated text from the raw Responses API JSON structure.
     *
     * @param  array<string, mixed>  $response
     */
    private function responseText(array $response): string
    {
        if (is_string($response['output_text'] ?? null)) {
            return trim($response['output_text']);
        }

        return trim((string) collect($response['output'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && ($item['type'] ?? null) === 'message')
            ->flatMap(fn (array $item) => $item['content'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && ($item['type'] ?? null) === 'output_text')
            ->pluck('text')
            ->implode("\n"));
    }

    private function calculateCost(?int $promptTokens, ?int $completionTokens): string
    {
        $inputCost = (($promptTokens ?? 0) / 1_000_000) * (float) config('services.openai.chat_input_cost_per_million');
        $outputCost = (($completionTokens ?? 0) / 1_000_000) * (float) config('services.openai.chat_output_cost_per_million');

        return number_format($inputCost + $outputCost, 8, '.', '');
    }
}
