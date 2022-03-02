<?php
namespace Database\Factories\V2;

use App\Models\V2\Nat;
use Illuminate\Database\Eloquent\Factories\Factory;

class NatFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Nat::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'destination_id' => 'fip-123456',
            'translated_id' => 'nic-654321',
            'action' => 'DNAT'
        ];
    }
}