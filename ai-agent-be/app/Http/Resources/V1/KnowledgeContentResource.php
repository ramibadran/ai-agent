<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeContentResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'source_url' => $this->source_url,
            'status' => $this->status,
            'version' => $this->version,
            'metadata' => $this->metadata,
            'published_at' => $this->published_at?->toIso8601String(),
            'indexed_at' => $this->indexed_at?->toIso8601String(),
            'index_runs_count' => $this->whenCounted('indexRuns'),
            'created_by' => $this->whenLoaded('createdBy', fn (): array => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
                'email' => $this->createdBy->email,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
