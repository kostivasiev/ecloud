<?php

namespace Tests\V2\FloatingIps;

use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
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

        $this->floatingIp = factory(FloatingIp::class)->create([
            'vpc_id' => $this->vpc()->id
        ]);

        $this->nsxServiceMock()->shouldReceive('delete')
            ->andReturnUsing(function () {
                return new Response(204, [], '');
            });
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['results' => [['id' => 0]]]));
            });
    }

    public function testAssignIsSuccessful()
    {
        $this->post('/v2/floating-ips/' . $this->floatingIp->id . '/assign', [
            'resource_id' => $this->nic()->id
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase('nats', [
            'destination_id' => $this->floatingIp->id,
            'destinationable_type' => 'fip',
            'translated_id' => $this->nic()->id,
            'translatedable_type' => 'nic'
        ],
            'ecloud'
        )->assertResponseStatus(202);

        $this->assertEquals($this->nic()->id, $this->floatingIp->resourceId);

        $this->get('/v2/floating-ips/' . $this->floatingIp->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->floatingIp->id,
            'resource_id' => $this->nic()->id
        ])->assertResponseStatus(200);
    }

    public function testUnAssignIsSuccessful()
    {
        $this->nat = factory(Nat::class)->create([
            'destination_id' => $this->floatingIp->id,
            'destinationable_type' => 'fip',
            'translated_id' => $this->nic()->id,
            'translatedable_type' => 'nic'
        ]);
        $this->post('/v2/floating-ips/' . $this->floatingIp->id . '/unassign', [
            'resource_id' => $this->nic()->id
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);
        $this->nat->refresh();
        $this->assertNotNull($this->nat->deleted_at);
    }
}
