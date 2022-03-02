<?php
namespace Database\Factories\V1;

use App\Models\V1\IopsTier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class IopsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IopsTier::class;

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
            'max_iops' => 500
        ];
    }
}
