<?php

namespace Tests\V1\Hosts;

use App\Models\V1\Host;
use App\Models\V1\Pod;
use App\Models\V1\Solution;
use Tests\V1\TestCase;

class DeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \DB::table('ucs_specification')->insert(
            array(
                'ucs_specification_id' => 1,
                'ucs_specification_active' => 'Yes',
                'ucs_specification_friendly_name' => '2 x Oct Core 2.7Ghz (E5-2680 v1) 128GB',
                'ucs_specification_cpu_qty' => 2,
                'ucs_specification_cpu_cores' => 8,
                'ucs_specification_cpu_speed' => '2.7Ghz',
                'ucs_specification_ram' => '128GB',
            )
        );
    }

    /**
     * Test for deleting a host with no SAN mapped to the solution
     * @return void
     */
    public function testDeleteHostNoSanMapped()
    {
        $pod = Pod::factory(1)->create()->first();

        $solution = Solution::factory(1)->create([
            'ucs_reseller_datacentre_id' => $pod->getKey()
        ])->first();

        $host = Host::factory(1)->create([
            'ucs_node_ucs_reseller_id' => $solution->getKey(),
            'ucs_node_internal_name' => 'Test Host 1'
        ])->first();

        $this->json('POST', '/v1/hosts/' . $host->getKey() . '/delete', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(404)
            ->assertJsonFragment([
                'title' => 'SAN not found',
                'detail' => "No SANS are found on the solution's pod"
            ]);
    }
}
