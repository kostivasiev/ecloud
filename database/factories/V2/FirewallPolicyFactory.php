<?php

namespace Database\Factories\V2;

use App\Models\V2\FirewallPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

class FirewallPolicyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FirewallPolicy::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => 'name',
            'sequence' => 10,
        ];
    }
}
