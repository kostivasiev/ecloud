<?php

namespace Tests\V1\HostSets;

use App\Models\V1\Pod;
use App\Models\V1\San;
use App\Models\V1\Solution;
use App\Models\V1\Storage;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\V1\TestCase;

class CreateTest extends TestCase
{
    /**
     * Test for creating a hostset with no SAN mapped to the solution
     * @return void
     */
    public function testCreateHostSetNoSan()
    {
        $pod = Pod::factory(1)->create()->first();

        $solution = Solution::factory(1)->create([
            'ucs_reseller_datacentre_id' => $pod->getKey()
        ])->first();

        $this->json('POST', '/v1/hostsets', [
            'solution_id' => $solution->getKey()
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write'
        ])->assertStatus(404)
            ->assertJsonFragment([
                'title' => 'SAN not found',
                'detail' => "No SANS are available on the solution's pod"
            ]);
    }

    /**
     * Test for create host with a mapped SAN
     * @return void
     */
    public function testCreateHostsetLinkedSan()
    {
        $pod = Pod::factory(1)->create()->first();

        $solution = Solution::factory(1)->create([
            'ucs_reseller_datacentre_id' => $pod->getKey()
        ])->first();

        $san = San::factory(1)->create([])->first();

        Storage::factory(1)->create([
            'ucs_datacentre_id' => $pod->getKey(),
            'server_id' => $san->getKey()
        ]);

        // This won't complete, but we can check that it's not throwing 'san not found'
        $response = $this->json('POST', '/v1/hostsets', [
            'solution_id' => $solution->getKey()
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertNotEquals(404, $response->getStatusCode());
    }
}

