<?php
namespace Database\Factories\V1;

use App\Models\V1\Firewall;
use Illuminate\Database\Eloquent\Factories\Factory;

class FirewallFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Firewall::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'servers_reseller_id' => 1,
            'servers_type' => 'virtual firewall',
            'servers_subtype_id' => 2,
            'servers_friendly_name' => $this->faker->company,
            'servers_hostname' => $this->faker->domainName,
            'servers_ip' => $this->faker->ipv4,
            'servers_active' => 'y',
            'servers_firewall_role' => 'Single',
            'servers_ecloud_ucs_reseller_id' => 1,
        ];
    }
}
