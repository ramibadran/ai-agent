<?php

namespace App\Services\V1;

use App\Models\ContentIndexRun;
use App\Models\KnowledgeContent;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KnowledgeContentService
{
    /**
     * @param  array{title: string, body: string, source_url?: string|null, metadata?: array<string, mixed>|null}  $attributes
     */
    public function create(User $creator, array $attributes): KnowledgeContent
    {
        return DB::transaction(function () use ($creator, $attributes): KnowledgeContent {
            $content = KnowledgeContent::query()->create([
                'created_by' => $creator->id,
                'title' => $attributes['title'],
                'slug' => $this->newSlug($attributes['title']),
                'body' => $attributes['body'],
                'source_url' => $attributes['source_url'] ?? null,
                'status' => 'published',
                'version' => 1,
                'content_checksum' => $this->checksum($attributes['title'], $attributes['body']),
                'metadata' => $attributes['metadata'] ?? null,
                'published_at' => now(),
            ]);

            $this->createPendingIndexRun($content);

            return $content;
        });
    }

    /**
     * @param  array{title?: string, body?: string, source_url?: string|null, metadata?: array<string, mixed>|null}  $attributes
     */
    public function update(KnowledgeContent $content, array $attributes): KnowledgeContent
    {
        return DB::transaction(function () use ($content, $attributes): KnowledgeContent {
            $content->fill(Arr::only($attributes, ['title', 'body', 'source_url', 'metadata']));

            if (! $content->isDirty()) {
                return $content;
            }

            $content->version++;
            $content->content_checksum = $this->checksum($content->title, $content->body);
            $content->indexed_at = null;
            $content->save();

            $this->createPendingIndexRun($content);

            return $content;
        });
    }

    public function delete(KnowledgeContent $content): void
    {
        DB::transaction(function () use ($content): void {
            $content->delete();
        });
    }

    private function createPendingIndexRun(KnowledgeContent $content): void
    {
        ContentIndexRun::query()->create([
            'knowledge_content_id' => $content->id,
            'status' => 'pending',
            'content_version' => $content->version,
            'content_checksum' => $content->content_checksum,
        ]);
    }

    private function checksum(string $title, string $body): string
    {
        return hash('sha256', $title."\n".$body);
    }

    private function newSlug(string $title): string
    {
        $base = Str::slug($title);

        return ($base === '' ? 'knowledge-content' : $base).'-'.Str::lower(Str::random(6));
    }
}
