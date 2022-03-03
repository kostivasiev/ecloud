<?php

namespace Tests\V1\Hosts;

use App\Models\V1\Host;
use App\Models\V1\Pod;
use App\Models\V1\Solution;
use Tests\V1\TestCase;

class DeleteTest extends TestCase
{
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
