<?php

namespace Tests\V1\Hosts;

use App\Models\V1\Host;
use App\Models\V1\Pod;
use App\Models\V1\Solution;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Test for deleting a host with no SAN mapped to the solution
     * @return void
     */
    public function testDeleteHostNoSanMapped()
    {
        $pod = factory(Pod::class, 1)->create()->first();

        $solution = factory(Solution::class, 1)->create([
            'ucs_reseller_datacentre_id' => $pod->id
        ])->first();

        $host = factory(Host::class, 1)->create([
            'ucs_node_ucs_reseller_id' => $solution->id,
            'ucs_node_internal_name' => 'Test Host 1'
        ])->first();

        $this->json('POST', '/v1/hosts/' . $host->id . '/delete', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeStatusCode(404)->seeJson([
            'title' => 'SAN not found',
            'detail' => "No SANS are found on the solution's pod"
        ]);
    }
}
