<?php

namespace Tests\Unit\Jobs\Nat;

use App\Jobs\Nat\UndeployCheck;
use App\Models\V2\IpAddress;
use App\Models\V2\Nat;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UndeployCheckTest extends TestCase
{
    public IpAddress $ipAddress;

    public function setUp(): void
    {
        parent::setUp();

        $this->ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'ip_address' => '10.3.4.5'
        ]);
    }

    public function testSucceeds()
    {
        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->destination()->associate($this->floatingIp());
        $nat->translated()->associate($this->ipAddress);
        $nat->action = NAT::ACTION_DNAT;
        $nat->save();

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [],
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new UndeployCheck($nat));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenStillExists()
    {
        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->destination()->associate($this->floatingIp());
        $nat->translated()->associate($this->ipAddress);
        $nat->action = NAT::ACTION_DNAT;
        $nat->save();

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'nat-test'
                        ],
                    ],
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new UndeployCheck($nat));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testNatNoSourceOrDestinationNicFails()
    {
        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_SNAT;
        $nat->save();

        Event::fake([JobFailed::class]);

        dispatch(new UndeployCheck($nat));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Could not find router scopable resource for source, destination or translated';
        });
    }
}
