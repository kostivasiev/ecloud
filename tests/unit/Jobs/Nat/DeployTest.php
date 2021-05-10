<?php

namespace Tests\unit\Jobs\Nat;

use App\Jobs\Nat\Deploy;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployTest extends TestCase
{
    protected Nat $nat;
    protected FloatingIp $floatingIp;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSourceNATExpectedRequest()
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
            $this->nat->source()->associate($this->nic);
            $this->nat->translated()->associate($this->floatingIp);
            $this->nat->action = NAT::ACTION_SNAT;
            $this->nat->save();
        });


        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test',
                [
                    'json' => [
                        "display_name" => "nat-test",
                        "description" => "nat-test",
                        "action" => "SNAT",
                        "translated_network" => "10.2.3.4",
                        "enabled" => true,
                        "logging" => false,
                        "firewall_match" => "MATCH_EXTERNAL_ADDRESS",
                        "source_network" => "10.3.4.5",
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->nat));

        Event::assertNotDispatched(JobFailed::class);
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


        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test',
                [
                    'json' => [
                        "display_name" => "nat-test",
                        "description" => "nat-test",
                        "action" => "DNAT",
                        "translated_network" => "10.3.4.5",
                        "enabled" => true,
                        "logging" => false,
                        "firewall_match" => "MATCH_EXTERNAL_ADDRESS",
                        "destination_network" => "10.2.3.4",
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->nat));

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

        dispatch(new Deploy($this->nat));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Nat Deploy Failed. Could not find NIC for source, destination or translated';
        });
    }

    public function testNatNoNicRouterFails()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
            ]);

            $this->network()->router_id = '';
            $this->network()->save();
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
            ]);

            $this->nat = app()->make(Nat::class);
            $this->nat->id = 'nat-test';
            $this->nat->source()->associate($this->nic);
            $this->nat->translated()->associate($this->floatingIp);
            $this->nat->action = NAT::ACTION_SNAT;
            $this->nat->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->nat));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Nat Deploy nic-test : No Router found for NIC network';
        });
    }
}
