<?php

namespace App\Services\V1;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class InternalAgentToolRouter
{
    public function __construct(
        private readonly KnowledgeRetriever $knowledgeRetriever,
        private readonly CommerceMcpClient $commerceMcpClient,
    ) {}

    /** @return array{context: list<array<string, mixed>>, sources: list<array<string, mixed>>} */
    public function retrieve(string $question): array
    {
        $context = [];
        $sources = [];

        foreach ($this->selectTools($question) as $index => $tool) {
            if ($tool['name'] === 'search_internal_knowledge') {
                $knowledgeSources = $this->knowledgeRetriever->retrieve((string) ($tool['arguments']['query'] ?? $question));
                $context = [...$context, ...$knowledgeSources];
                $sources = [...$sources, ...collect($knowledgeSources)->map(fn (array $source): array => ['source_type' => 'internal'] + $source)->all()];

                continue;
            }

            $result = $this->commerceMcpClient->call($tool['name'], $tool['arguments']);
            $text = $result['data'] === null ? $result['text'] : json_encode($result['data'], JSON_THROW_ON_ERROR);
            $source = [
                'source_type' => 'mcp',
                'title' => 'Atlas Commerce MCP · '.$tool['name'],
                'source_url' => null,
                'tool_name' => $tool['name'],
                'data' => $result['data'],
            ];

            $context[] = [
                'point_id' => 'mcp:'.$tool['name'].':'.$index,
                'content_id' => 0,
                'chunk_number' => $index + 1,
                'title' => $source['title'],
                'source_url' => null,
                'text' => $text,
                'score' => 1.0,
            ];
            $sources[] = $source;
        }

        return compact('context', 'sources');
    }

    /** @return list<array{name: string, arguments: array<string, mixed>}> */
    private function selectTools(string $question): array
    {
        $apiKey = (string) config('services.openai.api_key');

        if ($apiKey === '') {
            throw new InvalidArgumentException('OPENAI_API_KEY is required to select an internal tool.');
        }

        $tools = [[
            'type' => 'function',
            'name' => 'search_internal_knowledge',
            'description' => 'Search the internal knowledge base for document-based company information.',
            'parameters' => [
                'type' => 'object',
                'properties' => ['query' => ['type' => 'string']],
                'required' => ['query'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ]];

        foreach ($this->commerceMcpClient->toolDefinitions() as $definition) {
            $tools[] = ['type' => 'function'] + $definition;
        }

        $response = Http::baseUrl((string) config('services.openai.base_url'))
            ->withToken($apiKey)->acceptJson()->asJson()->timeout(30)
            ->post('responses', [
                'model' => config('services.openai.chat_model'),
                'instructions' => 'Choose every tool needed to answer the question, and no unnecessary tools. Use Commerce tools for factual sales, product ranking, or inventory questions. Use search_internal_knowledge for document knowledge. A combined question may require multiple tools. Never invent tool results.',
                'input' => $question,
                'tools' => $tools,
                'tool_choice' => 'required',
                'parallel_tool_calls' => true,
            ])->throw()->json();

        $calls = collect($response['output'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && ($item['type'] ?? null) === 'function_call')
            ->take(max(1, (int) config('services.internal_agent.max_tool_calls')))
            ->map(function (array $call): array {
                if (! is_string($call['name'] ?? null)) {
                    throw new InvalidArgumentException('The internal agent selected an invalid tool.');
                }

                $arguments = json_decode((string) ($call['arguments'] ?? '{}'), true, 512, JSON_THROW_ON_ERROR);

                return ['name' => $call['name'], 'arguments' => is_array($arguments) ? $arguments : []];
            })
            ->values()
            ->all();

        if ($calls === []) {
            throw new InvalidArgumentException('The internal agent did not select a tool.');
        }

        return $calls;
    }
}
