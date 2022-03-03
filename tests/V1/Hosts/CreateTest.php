<?php

namespace Tests\V1\Hosts;

use App\Models\V1\Host;
use App\Models\V1\Pod;
use App\Models\V1\San;
use App\Models\V1\Solution;
use App\Models\V1\Storage;
use Tests\V1\TestCase;

class CreateTest extends TestCase
{
    /**
     * Test for counting SAN's mapped to a solution
     * @return void
     */
    public function tesCountSolutionMappedSans()
    {
        $pod = Pod::factory(1)->create()->first();

        $solution = Solution::factory(1)->create([
            'ucs_reseller_datacentre_id' => $pod->getKey()
        ])->first();

        $this->assertEquals(0, $solution->pod->sans->count());

        // Add a SAN mapped to the solution
        Storage::factory(1)->create([
            'ucs_datacentre_id' => $pod->ucs_datacentre_id
        ]);

        $this->assertEquals(1, $solution->pod->sans->count());
    }

    /**
     * Test for creating a host with no SAN mapped to the solution
     * @return void
     */
    public function testCreateHostNoSan()
    {
        $pod = Pod::factory(1)->create()->first();

        $solution = Solution::factory(1)->create([
            'ucs_reseller_datacentre_id' => $pod->getKey()
        ])->first();

        $host = Host::factory(1)->create([
            'ucs_node_ucs_reseller_id' => $solution->getKey()
        ])->first();

        $this->json('POST', '/v1/hosts/' . $host->getKey() . '/create', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(404)
            ->assertJsonFragment([
                'title' => 'SAN not found',
                'detail' => "No SANS are found on the solution's pod"
            ]);
    }

    /**
     * Test for create host with a mapped SAN
     * @return void
     */
    public function testCreateHostLinkedSan()
    {
        $pod = Pod::factory(1)->create()->first();

        $solution = Solution::factory(1)->create([
            'ucs_reseller_datacentre_id' => $pod->getKey()
        ])->first();

        $host = Host::factory(1)->create([
            'ucs_node_ucs_reseller_id' => $solution->getKey()
        ])->first();

        $san = San::factory(1)->create([])->first();

        Storage::factory(1)->create([
            'ucs_datacentre_id' => $pod->getKey(),
            'server_id' => $san->getKey()
        ]);

        // This won't complete, but we can check that it's not getting caught at 'san not found'
        $response = $this->json('POST', '/v1/hosts/' . $host->getKey() . '/create', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertNotEquals(404, $response->getStatusCode());
    }
}
