<?php
namespace Database\Factories\V1;

use App\Models\V1\GpuProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GpuProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GpuProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => Str::uuid(),
            'name' => $this->faker->word(),
            'profile_name' => 'grid_v100d-32q',
            'card_type' => 'v100'
        ];
    }
}