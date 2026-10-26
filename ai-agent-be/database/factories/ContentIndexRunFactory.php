<?php

namespace Database\Factories;

use App\Models\ContentIndexRun;
use App\Models\KnowledgeContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentIndexRun>
 */
class ContentIndexRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'knowledge_content_id' => KnowledgeContent::factory(),
            'status' => 'pending',
            'content_version' => 1,
            'content_checksum' => hash('sha256', fake()->text()),
            'vector_count' => 0,
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the index run completed successfully.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'vector_count' => fake()->numberBetween(1, 100),
            'started_at' => now()->subSecond(),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the index run failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'failed',
            'started_at' => now()->subSecond(),
            'completed_at' => now(),
            'error_message' => 'Unable to index content.',
        ]);
    }
}
