<?php
namespace Database\Factories\V1;

use App\Models\V1\VolumeSet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VolumeSetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VolumeSet::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => Str::uuid(),
            'name' => $this->faker->name(),
            'ucs_reseller_id' => 1,
            'max_iops' => 500
        ];
    }
}
