<?php

namespace Database\Factories\V2;

use App\Models\V2\FirewallRulePort;
use Illuminate\Database\Eloquent\Factories\Factory;

class FirewallRulePortFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FirewallRulePort::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => 'name',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ];
    }
}
