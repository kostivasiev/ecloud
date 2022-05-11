<?php
namespace Database\Factories\V1;

use App\Models\V1\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'metadata_key' => $this->faker->word(),
            'metadata_value' => $this->faker->word(),
            'metadata_created' => $this->faker->dateTime(),
            'metadata_reseller_id' => 1,
            'metadata_resource' => 'server',
            'metadata_resource_id' => 123,
            'metadata_createdby' => 'API Client',
            'metadata_createdby_id' => 1,
        ];
    }
}
