<?php

namespace Tests\Hosts;

use App\Models\V1\Storage;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\Host;
use App\Models\V1\Solution;
use App\Models\V1\Pod;
use App\Models\V1\San;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }


    /**
     * Test for counting SAN's mapped to a solution
     * @return void
     */
    public function tesCountSolutionMappedSans()
    {
        $pod = factory(Pod::class, 1)->create()->first();

        $solution = factory(Solution::class, 1)->create([
            'ucs_reseller_datacentre_id' => $pod->getKey()
        ])->first(); 

        $this->assertEquals(0, $solution->pod->sans->count());

        // Add a SAN mapped to the solution
        $storage = factory(Storage::class, 1)->create([
            'ucs_datacentre_id' => $pod->ucs_datacentre_id
        ])->first();

        $this->assertEquals(1, $solution->pod->sans->count());
    }

    /**
     * Test for creating a host with no SAN mapped to the solution
     * @return void
     */
    public function testCreateHostNoSan()
    {
        $pod = factory(Pod::class, 1)->create()->first();

        $solution = factory(Solution::class, 1)->create([
            'ucs_reseller_datacentre_id' => $pod->getKey()
        ])->first();

        $host = factory(Host::class, 1)->create([
            'ucs_node_ucs_reseller_id' => $solution->getKey()
        ])->first();

       $this->json('POST', '/v1/hosts/' . $host->getKey() . '/create', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeStatusCode(404)->seeJson([
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
        $pod = factory(Pod::class, 1)->create()->first();

        $solution = factory(Solution::class, 1)->create([
            'ucs_reseller_datacentre_id' => $pod->getKey()
        ])->first(); //pod_id

        $host = factory(Host::class, 1)->create([
            'ucs_node_ucs_reseller_id' => $solution->getKey()
        ])->first(); //solution_id

        $san = factory(San::class, 1)->create([])->first();

        $storage = factory(Storage::class, 1)->create([
            'ucs_datacentre_id' => $pod->getKey(),
            'server_id' => $san->getKey()
        ])->first(); //pod_id

        // This won't complete, but we can check that it's not getting caught at 'san not found'
        $this->json('POST', '/v1/hosts/' . $host->getKey() . '/create', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeStatusCode(503);
    }
}
