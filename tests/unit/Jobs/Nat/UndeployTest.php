<?php

namespace Tests\unit\Jobs\Nat;

use App\Jobs\Nat\Undeploy;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    protected Nat $nat;
    protected FloatingIp $floatingIp;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testDestinationNATExpectedRequest()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
                'ip_address' => '10.3.4.5',
            ]);
            $this->nat = app()->make(Nat::class);
            $this->nat->id = 'nat-test';
            $this->nat->destination()->associate($this->floatingIp);
            $this->nat->translated()->associate($this->nic);
            $this->nat->action = NAT::ACTION_DNAT;
            $this->nat->save();
        });

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

        dispatch(new Undeploy($this->nat));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNatNoSourceOrDestinationNicFails()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
            ]);
            $this->nat = app()->make(Nat::class);
            $this->nat->id = 'nat-test';
            $this->nat->translated()->associate($this->floatingIp);
            $this->nat->action = NAT::ACTION_SNAT;
            $this->nat->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->nat));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Could not find router scopable resource for source, destination or translated';
        });
    }

    public function testSkipsWhenNatAlreadyRemoved()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
                'ip_address' => '10.3.4.5',
            ]);
            $this->nat = app()->make(Nat::class);
            $this->nat->id = 'nat-test';
            $this->nat->destination()->associate($this->floatingIp);
            $this->nat->translated()->associate($this->nic);
            $this->nat->action = NAT::ACTION_DNAT;
            $this->nat->save();
        });

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test'])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        $this->nsxServiceMock()->shouldNotReceive('delete');

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->nat));

        Event::assertNotDispatched(JobFailed::class);
    }
}
