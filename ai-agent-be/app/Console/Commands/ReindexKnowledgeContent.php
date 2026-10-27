<?php

namespace App\Console\Commands;

use App\Models\KnowledgeContent;
use App\Services\V1\KnowledgeContentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('knowledge:reindex {--content= : Reindex one knowledge-content ID instead of all published content}')]
#[Description('Synchronize published knowledge content from MySQL to Qdrant')]
class ReindexKnowledgeContent extends Command
{
    public function handle(KnowledgeContentService $knowledgeContentService): int
    {
        $query = KnowledgeContent::query()
            ->where('status', 'published')
            ->orderBy('id');

        if ($contentId = $this->option('content')) {
            $query->whereKey($contentId);
        }

        $contents = $query->get();

        if ($contents->isEmpty()) {
            $this->warn('No published knowledge content was found.');

            return self::FAILURE;
        }

        $failures = 0;

        foreach ($contents as $content) {
            try {
                $knowledgeContentService->reindex($content);
                $this->info("Indexed [{$content->id}] {$content->title}");
            } catch (Throwable $exception) {
                $failures++;
                $this->error("Failed [{$content->id}] {$content->title}: {$exception->getMessage()}");
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
