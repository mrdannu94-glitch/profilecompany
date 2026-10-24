<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\ContentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentItem>
 */
class ContentItemFactory extends Factory
{
    protected $model = ContentItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content_type' => 'berita',
            'status' => ContentStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => ContentStatus::Published]);
    }
}
