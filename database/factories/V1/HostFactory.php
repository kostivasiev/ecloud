<?php
namespace Database\Factories\V1;

use App\Models\V1\Host;
use Illuminate\Database\Eloquent\Factories\Factory;

class HostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Host::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'ucs_node_reseller_id' => 1,
            'ucs_node_ucs_reseller_id' => 1,
            'ucs_node_datacentre_id' => 1,
            'ucs_node_specification_id' => 1,
            'ucs_node_status' => 'Completed',
            'ucs_node_location_id' => 1,
            'ucs_node_eth0_mac' => 'AA:BB:CC:DD:EE:FF',
        ];
    }
}
