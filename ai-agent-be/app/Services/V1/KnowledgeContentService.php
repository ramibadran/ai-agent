<?php

namespace App\Services\V1;

use App\Models\ContentIndexRun;
use App\Models\KnowledgeContent;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class KnowledgeContentService
{
    public function __construct(
        private readonly KnowledgeContentIndexer $indexer,
        private readonly QdrantClient $qdrant,
    ) {}

    /**
     * @param  array{title: string, body: string, source_url?: string|null, metadata?: array<string, mixed>|null}  $attributes
     */
    public function create(User $creator, array $attributes): KnowledgeContent
    {
        [$content, $indexRun] = DB::transaction(function () use ($creator, $attributes): array {
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

            $indexRun = $this->createPendingIndexRun($content);

            return [$content, $indexRun];
        });

        $this->index($indexRun);

        return $content->fresh() ?? $content;
    }

    /**
     * @param  array{title?: string, body?: string, source_url?: string|null, metadata?: array<string, mixed>|null}  $attributes
     */
    public function update(KnowledgeContent $content, array $attributes): KnowledgeContent
    {
        $indexRun = DB::transaction(function () use ($content, $attributes): ?ContentIndexRun {
            $content->fill(Arr::only($attributes, ['title', 'body', 'source_url', 'metadata']));

            if (! $content->isDirty()) {
                return null;
            }

            $content->version++;
            $content->content_checksum = $this->checksum($content->title, $content->body);
            $content->indexed_at = null;
            $content->save();

            return $this->createPendingIndexRun($content);
        });

        if ($indexRun !== null) {
            $this->index($indexRun);
        }

        return $content->fresh() ?? $content;
    }

    public function delete(KnowledgeContent $content): void
    {
        $this->qdrant->deleteContentVectors($content->id);
        $content->delete();
    }

    private function createPendingIndexRun(KnowledgeContent $content): ContentIndexRun
    {
        $indexRun = ContentIndexRun::query()->create([
            'knowledge_content_id' => $content->id,
            'status' => 'pending',
            'content_version' => $content->version,
            'content_checksum' => $content->content_checksum,
        ]);

        return $indexRun;
    }

    private function index(ContentIndexRun $indexRun): void
    {
        try {
            $this->indexer->index($indexRun);
        } catch (Throwable $exception) {
            $indexRun->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => str($exception->getMessage())->limit(1000)->toString(),
            ]);

            throw $exception;
        }
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
