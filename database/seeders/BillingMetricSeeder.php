<?php

namespace Database\Seeders;

use App\Models\V2\BillingMetric;
use App\Models\V2\HostSpec;
use App\Models\V2\LoadBalancerSpecification;
use Illuminate\Database\Seeder;

class BillingMetricSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            ['key' => 'floating-ip.count', 'value' => '1'],
            ['key' => 'hostgroup', 'value' => '1'],
            ['key' => 'host.hs-123456', 'value' => '1'],
            ['key' => 'host.license.windows', 'value' => '1'],
            ['key' => 'image.private', 'value' => '1'],
            ['key' => 'backup.quota', 'value' => '1'],
            ['key' => 'license.plesk', 'value' => '1'],
            ['key' => 'license.cpanel', 'value' => '1'],
            ['key' => 'ram.capacity', 'value' => '1'],
            ['key' => 'ram.capacity.high', 'value' => '1'],
            ['key' => 'vcpu.count', 'value' => '1'],
            ['key' => 'license.windows', 'value' => '1'],
            ['key' => 'load-balancer.lbs-1234567', 'value' => '1'],
            ['key' => 'throughput.50mb', 'value' => '1'],
            ['key' => 'disk.capacity.300', 'value' => '1'],
            ['key' => 'network.advanced', 'value' => '1'],
            ['key' => 'vpn.session.ipsec', 'value' => '1'],
        ];
        foreach ($items as $item) {
            BillingMetric::factory()->create($item);
        }

        HostSpec::factory()->create(['id' => 'hs-123456']);
        LoadBalancerSpecification::factory()->create(['id' => 'lbs-1234567']);
    }
}
