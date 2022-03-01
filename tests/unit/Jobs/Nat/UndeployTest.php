<?php

namespace Tests\unit\Jobs\Nat;

use App\Jobs\Nat\Undeploy;
use App\Models\V2\IpAddress;
use App\Models\V2\Nat;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UndeployTest extends TestCase
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

    public function testDestinationNATExpectedRequest()
    {
        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->destination()->associate($this->floatingIp());
        $nat->translated()->associate($this->ipAddress);
        $nat->action = NAT::ACTION_DNAT;
        $nat->save();

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $this->nsxServiceMock()->expects('delete')
            ->withArgs(['/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($nat));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNatNoSourceOrDestinationNatableFails()
    {
        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_SNAT;
        $nat->save();

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($nat));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Could not find router scopable resource for source, destination or translated';
        });
    }

    public function testSkipsWhenNatAlreadyRemoved()
    {
        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->destination()->associate($this->floatingIp());
        $nat->translated()->associate($this->ipAddress);
        $nat->action = NAT::ACTION_DNAT;
        $nat->save();

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test'])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        $this->nsxServiceMock()->shouldNotReceive('delete');

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($nat));

        Event::assertNotDispatched(JobFailed::class);
    }
}
