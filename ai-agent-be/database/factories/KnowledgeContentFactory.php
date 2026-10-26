<?php

namespace Database\Factories;

use App\Models\KnowledgeContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeContent>
 */
class KnowledgeContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'created_by' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'body' => fake()->paragraphs(5, true),
            'source_url' => fake()->optional()->url(),
            'status' => 'draft',
            'version' => 1,
            'content_checksum' => hash('sha256', fake()->text()),
            'metadata' => ['tags' => fake()->words(3)],
            'published_at' => null,
            'indexed_at' => null,
        ];
    }

    /**
     * Indicate that the content is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
