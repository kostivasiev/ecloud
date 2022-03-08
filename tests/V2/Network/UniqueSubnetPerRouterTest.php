<?php

namespace Tests\V2\Network;

use App\Events\V2\Task\Created as TaskCreated;
use App\Events\V2\Sync\Created as SyncCreated;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UniqueSubnetPerRouterTest extends TestCase
{
    protected AvailabilityZone $availabilityZone;
    protected Network $network;
    protected Network $network2;
    protected Region $region;
    protected Router $router;
    protected Router $router2;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = Region::factory()->create([
            'name' => 'testregion',
        ]);
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ]);
        $this->router2 = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ]);
        $this->network = Network::factory()->create([
            'router_id' => $this->router->id,
            'subnet' => '10.0.0.1/30',
        ]);
        $this->network2 = Network::factory()->create([
            'router_id' => $this->router->id,
            'subnet' => '10.0.0.2/30',
        ]);
    }

    public function testOverlapDetectionWorks()
    {
        $this->asAdmin()
            ->post(
                '/v2/networks',
                [
                    'name' => 'Manchester Network',
                    'router_id' => $this->router->id,
                    'subnet' => '10.0.0.1/22'
                ]
            )->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The subnet must not overlap an existing CIDR range',
            ])->assertStatus(422);
    }

    public function testSuccessfulCreation()
    {
        Event::fake([TaskCreated::class]);
        $this->asAdmin()
            ->post(
                '/v2/networks',
                [
                    'name' => 'Manchester Network',
                    'router_id' => $this->router2->id,
                    'subnet' => '10.0.0.1/22'
                ]
            )->assertStatus(202);
        Event::assertDispatched(TaskCreated::class);
    }

    public function testPatchIsSuccessful()
    {
        Event::fake([TaskCreated::class, SyncCreated::class]);

        // update the record using the same data. Before the fix the same subnet for the same
        // record would result in an overlapping subnet error.
        $this->asAdmin()
            ->patch(
                '/v2/networks/' . $this->network()->id,
                [
                    'name' => 'Updated Network',
                    'router_id' => $this->network()->router_id,
                    'subnet' => $this->network()->subnet,
                ]
            )->assertStatus(202);
    }
}
