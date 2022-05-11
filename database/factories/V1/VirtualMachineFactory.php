<?php

namespace Database\Factories\V1;

use App\Models\V1\VirtualMachine;
use Illuminate\Database\Eloquent\Factories\Factory;

class VirtualMachineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VirtualMachine::class;

    protected array $platforms = [
        'Windows',
        'Linux'
    ];

    protected array $licenses = [
        'Linux' => [
            'CentOS6 x86_64',
            'CentOS7 x86_64'
        ],
        'Windows' => [
            ''
        ]
    ];

    protected array $environments = [
        'Hybrid',
        'Public',
        'Private',
        'Burst'
    ];

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $platform = $this->faker->randomElement($this->platforms);

        $serverLicenseName = $this->faker->randomElement($this->licenses[$platform]);

        return [
            'servers_reseller_id' => 1,
            'servers_type' => 'ecloud vm',
            'servers_subtype_id' => 1,
            'servers_ecloud_ucs_reseller_id' => 1,
            'servers_friendly_name' => $this->faker->sentence(2),
            'servers_hostname' => '172.16.28.173.srvlist.ukfast.net',
            'servers_netnios_name' => '172.16.28.173.srvlist.ukfast.net',
            'servers_cpu' => rand(1, 5),
            'servers_memory' => rand(1, 5),
            'servers_hdd' => rand(20, 300),
            'servers_platform' => $platform,
            'servers_license' => $serverLicenseName,
            'servers_backup' => $this->faker->boolean,
            'servers_advanced_support' => $this->faker->boolean,
            'servers_status' => 'Complete',
            'servers_ecloud_type' => $this->faker->randomElement($this->environments),
        ];
    }
}
