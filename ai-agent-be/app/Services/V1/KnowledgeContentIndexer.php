<?php

namespace App\Services\V1;

use App\Contracts\EmbeddingProvider;
use App\Models\ContentIndexRun;
use Illuminate\Support\Facades\DB;

class KnowledgeContentIndexer
{
    public function __construct(private readonly KnowledgeContentChunker $chunker, private readonly EmbeddingProvider $embeddingProvider, private readonly QdrantClient $qdrant) {}

    public function index(ContentIndexRun $indexRun): void
    {
        $content = $indexRun->knowledgeContent;
        if ($content === null) {
            return;
        }
        if ($content->version !== $indexRun->content_version || $content->content_checksum !== $indexRun->content_checksum) {
            $indexRun->update(['status' => 'superseded', 'completed_at' => now(), 'error_message' => null]);

            return;
        }
        $indexRun->update(['status' => 'processing', 'started_at' => now(), 'completed_at' => null, 'error_message' => null]);
        $chunks = $this->chunker->chunk($content->title."\n\n".$content->body);
        if ($chunks === []) {
            throw new \RuntimeException('Knowledge content does not contain indexable text.');
        }
        $this->qdrant->replaceContentVectors($content, $chunks, $this->embeddingProvider->embed($chunks));
        DB::transaction(function () use ($content, $indexRun, $chunks): void {
            $content->refresh();
            $indexRun->refresh();
            if ($content->version !== $indexRun->content_version || $content->content_checksum !== $indexRun->content_checksum) {
                $indexRun->update(['status' => 'superseded', 'completed_at' => now()]);

                return;
            }
            $content->update(['indexed_at' => now()]);
            $indexRun->update(['status' => 'completed', 'vector_count' => count($chunks), 'completed_at' => now()]);
        });
    }
}
