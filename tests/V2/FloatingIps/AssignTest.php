<?php

namespace Tests\V2\FloatingIps;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\NsxService;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AssignTest extends TestCase
{
    use DatabaseMigrations;

    protected $floatingIp;
    protected $nic;
    protected $nat;
    protected $region;
    protected $router;
    protected $vpc;
    protected $dhcp;
    protected $availability_zone;
    protected $instance;
    protected $network;

    public function setUp(): void
    {
        parent::setUp();

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');
            $mockEncryptionServiceProvider->shouldReceive('decrypt')->andReturn('somepassword');
            return $mockEncryptionServiceProvider;
        });

        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        factory(Credential::class)->create([
            'name' => 'NSX',
            'resource_id' => $this->availability_zone->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availability_zone->id,
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availability_zone->id
        ]);
        $this->network = factory(Network::class)->create([
            'name' => 'Manchester Network',
            'router_id' => $this->router->id
        ]);
        $this->nic = factory(Nic::class)->create([
            'mac_address' => 'abcd',
            'instance_id' => $this->instance->id,
            'network_id' => $this->network->id,
        ]);
        $this->floatingIp = factory(FloatingIp::class)->create([
            'vpc_id' => $this->vpc->id
        ]);
        $nsxService = app()->makeWith(NsxService::class, [$this->availability_zone]);
        $mockNsxService = \Mockery::mock($nsxService)->makePartial();
        app()->bind(NsxService::class, function () use ($mockNsxService) {
            $mockNsxService->shouldReceive('delete')
                ->andReturnUsing(function () {
                    return new Response(204, [], '');
                });
            $mockNsxService->shouldReceive('get')
                ->andReturnUsing(function () {
                    return new Response(200, [], json_encode(['results' => [['id' => 0]]]));
                });
            return $mockNsxService;
        });
    }

    public function testAssignIsSuccessful()
    {
        $this->post('/v2/floating-ips/' . $this->floatingIp->id . '/assign', [
            'resource_id' => $this->nic->id
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase('nats', [
            'destination_id' => $this->floatingIp->id,
            'destinationable_type' => 'fip',
            'translated_id' => $this->nic->id,
            'translatedable_type' => 'nic'
        ],
            'ecloud'
        )->assertResponseStatus(202);

        $this->assertEquals($this->nic->id, $this->floatingIp->resourceId);

        $this->get('/v2/floating-ips/' . $this->floatingIp->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->floatingIp->id,
            'resource_id' => $this->nic->id
        ])->assertResponseStatus(200);
    }

    public function testUnAssignIsSuccessful()
    {
        $this->nat = factory(Nat::class)->create([
            'destination_id' => $this->floatingIp->id,
            'destinationable_type' => 'fip',
            'translated_id' => $this->nic->id,
            'translatedable_type' => 'nic'
        ]);
        $this->post('/v2/floating-ips/' . $this->floatingIp->id . '/unassign', [
            'resource_id' => $this->nic->id
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);
        $this->nat->refresh();
        $this->assertNotNull($this->nat->deleted_at);
    }
}
