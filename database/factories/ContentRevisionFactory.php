<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\ContentItem;
use App\Models\ContentRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentRevision>
 */
class ContentRevisionFactory extends Factory
{
    protected $model = ContentRevision::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'content_item_id' => ContentItem::factory(),
            'author_id' => User::factory(),
            'revision_number' => 1,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'excerpt' => fake()->sentence(12),
            'body' => fake()->paragraphs(2, true),
            'workflow_status' => ContentStatus::Draft,
        ];
    }
}
