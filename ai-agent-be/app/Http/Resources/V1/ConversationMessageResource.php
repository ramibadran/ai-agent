<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'sources' => $this->when($this->role === 'assistant' && $this->retrieval_context !== null, fn (): array => collect($this->retrieval_context)
                ->map(fn (array $source): array => [
                    'source_type' => $source['source_type'] ?? 'internal',
                    'content_id' => $source['content_id'] ?? null,
                    'chunk_number' => $source['chunk_number'] ?? null,
                    'title' => $source['title'] ?? 'Unknown source',
                    'source_url' => $source['source_url'] ?? null,
                    'score' => $source['score'] ?? null,
                    'tool_name' => $source['tool_name'] ?? null,
                    'data' => $source['data'] ?? null,
                ])
                ->all()),
            'observability' => $this->when($this->role === 'assistant', fn (): array => [
                'model' => $this->model,
                'prompt_tokens' => $this->prompt_tokens,
                'completion_tokens' => $this->completion_tokens,
                'total_tokens' => $this->total_tokens,
                'latency_ms' => $this->latency_ms,
                'cost_usd' => $this->cost_usd,
                'status' => $this->status,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
