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
            ['key' => 'floating-ip.count', 'value' => '1', 'friendly_name' => null],
            ['key' => 'hostgroup', 'value' => '1', 'friendly_name' => null],
            ['key' => 'host.hs-123456', 'value' => '1', 'friendly_name' => null],
            ['key' => 'host.license.windows', 'value' => '1', 'friendly_name' => null],
            ['key' => 'image.private', 'value' => '1', 'friendly_name' => null],
            ['key' => 'backup.quota', 'value' => '1', 'friendly_name' => null],
            ['key' => 'license.plesk', 'value' => '1', 'friendly_name' => null],
            ['key' => 'license.cpanel', 'value' => '1', 'friendly_name' => null],
            ['key' => 'ram.capacity', 'value' => '1', 'friendly_name' => null],
            ['key' => 'ram.capacity.high', 'value' => '1', 'friendly_name' => null],
            ['key' => 'vcpu.count', 'value' => '1', 'friendly_name' => null],
            ['key' => 'license.windows', 'value' => '1', 'friendly_name' => null],
            ['key' => 'load-balancer.lbs-1234567', 'value' => '1', 'friendly_name' => null],
            ['key' => 'throughput.50mb', 'value' => '1', 'friendly_name' => null],
            ['key' => 'disk.capacity.300', 'value' => '1', 'friendly_name' => null],
            ['key' => 'network.advanced', 'value' => '1', 'friendly_name' => null],
            ['key' => 'vpn.session.ipsec', 'value' => '1', 'friendly_name' => null],
        ];
        foreach ($items as $item) {
            factory(BillingMetric::class)->create($item);
        }

        factory(HostSpec::class)->create(['id' => 'hs-123456']);
        factory(LoadBalancerSpecification::class)->create(['id' => 'lbs-1234567']);
    }
}
