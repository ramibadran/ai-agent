<?php

namespace Database\Seeders;

use App\Models\ContentIndexRun;
use App\Models\KnowledgeContent;
use Illuminate\Database\Seeder;

class ContentIndexRunSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        KnowledgeContent::query()
            ->where('status', 'published')
            ->each(function (KnowledgeContent $content): void {
                ContentIndexRun::query()->firstOrCreate(
                    [
                        'knowledge_content_id' => $content->id,
                        'content_version' => $content->version,
                        'content_checksum' => $content->content_checksum,
                    ],
                    [
                        'status' => 'pending',
                        'vector_count' => 0,
                    ],
                );
            });
    }
}
