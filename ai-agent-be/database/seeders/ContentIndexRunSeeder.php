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
        KnowledgeContent::factory()
            ->count(5)
            ->create()
            ->each(function (KnowledgeContent $content): void {
                ContentIndexRun::factory()
                    ->for($content)
                    ->completed()
                    ->create([
                        'content_version' => $content->version,
                        'content_checksum' => $content->content_checksum,
                    ]);
            });
    }
}
