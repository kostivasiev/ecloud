<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use Database\Seeders\ResourceTierSeeder;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->instanceModel();

        $this->kingpinServiceMock()->shouldReceive('get')->andReturn(
            new Response(200, [], json_encode([
                'powerState' => 'poweredOn',
                'toolsRunningStatus' => 'guestToolsRunning'
            ]))
        );
    }

    public function testGetCollection()
    {
        $this->asAdmin()->get('/v2/instances')
            ->assertJsonFragment([
            'id' => $this->instanceModel()->id,
            'name' => $this->instanceModel()->name,
            'vpc_id' => $this->instanceModel()->vpc_id,
            'platform' => 'Linux',
        ])->assertStatus(200);
    }

    public function testCantSeeHiddenResource()
    {
        $hidden = Instance::factory()->create([
            'is_hidden' => true,
            'vpc_id' => $this->vpc()->id,
        ]);

        $this->asUser()->get('/v2/instances/' . $hidden->id)
            ->assertJsonFragment([
            'title' => 'Not found',
            'detail' => 'No Instance with that ID was found',
        ])->assertStatus(404);
    }

    public function testGetResource()
    {
        $this->asAdmin()->get('/v2/instances/' . $this->instanceModel()->id)
            ->assertJsonFragment([
            'id' => $this->instanceModel()->id,
            'name' => $this->instanceModel()->name,
            'vpc_id' => $this->instanceModel()->vpc_id,
            'image_id' => $this->image()->id,
        ])->assertJsonFragment([
            'platform' => 'Linux',
        ])->assertStatus(200);
    }

    public function testGetFloatingIps()
    {
        // Assign ip address to instance's NIC
        $this->ipAddress()->nics()->sync($this->nic());

        // Assign fIP to the IP address
        $this->assignFloatingIp($this->floatingIp(), $this->ipAddress());

        $this->asAdmin()->get('/v2/instances/' . $this->instanceModel()->id . '/floating-ips')
            ->assertJsonFragment([
                'id' => 'fip-test',
            ])
            ->assertStatus(200);
    }

    public function testGetResourceTierInResponse()
    {
        AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);

        (new ResourceTierSeeder())->run();

        $this->instanceModel()->setAttribute('host_group_id', 'hg-99f9b758')->save();

        $this->asAdmin()->get('/v2/instances/' . $this->instanceModel()->id)
            ->assertJsonFragment([
                'resource_tier_id' => 'rt-aaaaaaaa',
                'host_group_id' => null,
            ])->assertStatus(200);
    }

    public function testGPrivateHostGroupReturnsNullResourceTier()
    {
        AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);

        (new ResourceTierSeeder())->run();

        $this->instanceModel()->setAttribute('host_group_id', $this->hostGroup()->id)->save();

        $this->asAdmin()->get('/v2/instances/' . $this->instanceModel()->id)
            ->assertJsonFragment([
                'resource_tier_id' => null,
                'host_group_id' => $this->hostGroup()->id,
            ])->assertStatus(200);
    }
}
