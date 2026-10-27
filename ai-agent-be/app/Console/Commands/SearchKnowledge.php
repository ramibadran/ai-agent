<?php

namespace App\Console\Commands;

use App\Services\V1\KnowledgeRetriever;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('knowledge:search {question : The question to search for} {--limit=5 : Maximum number of chunks to return}')]
#[Description('Search published knowledge with semantic similarity')]
class SearchKnowledge extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(KnowledgeRetriever $knowledgeRetriever): int
    {
        $results = $knowledgeRetriever->retrieve(
            question: (string) $this->argument('question'),
            limit: (int) $this->option('limit'),
        );

        if ($results === []) {
            $this->warn('No relevant knowledge chunks were found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Score', 'Content', 'Chunk', 'Text'],
            collect($results)->map(fn (array $result): array => [
                number_format($result['score'], 4),
                $result['title'],
                $result['chunk_number'],
                str($result['text'])->limit(180),
            ])->all(),
        );

        return self::SUCCESS;
    }
}
