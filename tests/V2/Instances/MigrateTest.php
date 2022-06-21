<?php

namespace Tests\V2\Instances;

use App\Events\V2\Task\Created;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class MigrateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testMigrateToPrivate()
    {
        $this->isWithinCapacity();
        Event::fake(Created::class);

        $this->post(
            '/v2/instances/' . $this->instanceModel()->id . '/migrate',
            [
                'host_group_id' => $this->hostGroup()->id
            ],
        )->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testMigrateToPublic()
    {
        $this->isWithinCapacity();
        Event::fake(Created::class);

        $this->post('/v2/instances/' . $this->instanceModel()->id . '/migrate')
            ->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testIsCompatiblePlatformFails()
    {
        $this->isWithinCapacity();
        Event::fake();

        $instance = Instance::withoutEvents(function () {
            return Instance::factory()->create([
                'id' => 'i-' . uniqid(),
                'vpc_id' => $this->vpc()->id,
                'name' => 'Test Instance ' . uniqid(),
            ]);
        });

        Image::factory()->create([
            'id' => 'img-' . uniqid(),
            'platform' => 'Windows'
        ])->instances()->save($instance);


        $this->hostGroup()->windows_enabled = false;
        $this->hostGroup()->saveQuietly();

        $this->post(
            '/v2/instances/' . $instance->id . '/migrate',
            [
                'host_group_id' => $this->hostGroup()->id
            ],
        )->assertStatus(422);
    }

    public function testInAffinityGroupFails()
    {
        $this->isWithinCapacity();
        AffinityRuleMember::withoutEvents(function () {
            return AffinityRuleMember::factory()
                ->for(AffinityRule::factory()
                    ->for($this->vpc())
                    ->for($this->availabilityZone())
                    ->create([
                        'id' => 'ar-test',
                        'name' => 'ar-test',
                        'type' => 'anti-affinity',
                    ]))
                ->for($this->instanceModel())
                ->create([
                    'id' => 'arm-test',
                ]);
        });

        $this->post(
            '/v2/instances/' . $this->instanceModel()->id . '/migrate',
            [
                'host_group_id' => $this->hostGroup()->id
            ],
        )
            ->assertSeeText('Forbidden')
            ->assertSeeText('cannot be moved')
            ->assertStatus(403);
    }

    public function testCapacityCheckFails()
    {
        $this->isOutsideCapacity();

        $this->post(
            '/v2/instances/' . $this->instanceModel()->id . '/migrate',
            [
                'host_group_id' => $this->hostGroup()->id
            ],
        )->assertJsonFragment([
            'title' => 'Conflict',
            'details' => 'There are insufficient resources to migrate to this hostgroup.',
        ])->assertStatus(409);
    }

    private function isWithinCapacity(): static
    {
        $this->kingpinServiceMock()
            ->allows('get')
            ->with(
                sprintf(KingpinService::GET_CAPACITY_URI, $this->vpc()->id, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupId' => $this->hostGroup()->id,
                    'cpuUsage' => 0,
                    'cpuUsedMHz' => 10,
                    'cpuCapacityMHz' => 100,
                    'ramUsage' => 0,
                    'ramUsedMB' => 10,
                    'ramCapacityMB' => 100,
                ]));
            });
        return $this;
    }

    private function isOutsideCapacity(): static
    {
        $this->kingpinServiceMock()
            ->allows('get')
            ->with(
                sprintf(KingpinService::GET_CAPACITY_URI, $this->vpc()->id, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupId' => $this->hostGroup()->id,
                    'cpuUsage' => 0,
                    'cpuUsedMHz' => 90,
                    'cpuCapacityMHz' => 100,
                    'ramUsage' => 0,
                    'ramUsedMB' => 90,
                    'ramCapacityMB' => 100,
                ]));
            });
        return $this;
    }
}
