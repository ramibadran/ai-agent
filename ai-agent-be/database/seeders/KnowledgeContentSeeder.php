<?php

namespace Database\Seeders;

use App\Models\KnowledgeContent;
use App\Models\User;
use Illuminate\Database\Seeder;

class KnowledgeContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administrator = User::factory()->create();

        KnowledgeContent::factory()
            ->count(10)
            ->for($administrator, 'createdBy')
            ->create();
    }
}
