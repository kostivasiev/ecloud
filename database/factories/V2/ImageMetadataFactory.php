<?php

namespace Database\Factories\V2;

use App\Models\V2\ImageMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageMetadataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ImageMetadata::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'key' => 'test.key',
            'value' => 'test.value',
        ];
    }
}
