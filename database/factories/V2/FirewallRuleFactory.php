<?php

namespace Database\Factories\V2;

use App\Models\V2\FirewallRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class FirewallRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FirewallRule::class;

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
            'source' => '192.168.100.1',
            'destination' => '212.22.18.10',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ];
    }
}
