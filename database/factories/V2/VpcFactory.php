<?php
namespace Database\Factories\V2;

use App\Models\V2\Vpc;
use Illuminate\Database\Eloquent\Factories\Factory;

class VpcFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Vpc::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Virtual Private Cloud Name',
            'reseller_id' => 1,
        ];
    }
}
