<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Events\V2\Nic\Saved;
use App\Events\V2\Nic\Saving;
use App\Jobs\Instance\Deploy\ConfigureNics;
use App\Models\V2\Nic;
use App\Rules\V2\IpAvailable;
use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\QueryException;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ConfigureNicsTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testIPAvailableAssignsIP()
    {
        Event::fake([Saving::class, Saved::class]);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/vpc-test/instance/i-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'nics' => [
                        [
                            'macAddress' => 'AA:BB:CC:DD:EE:FF'
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id . '/dhcp-static-binding-configs?cursor=')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });

        $this->network()->subnet = '10.0.0.0/24';
        $this->network()->save();

        $job = new ConfigureNics($this->instance());
        $job->handle();

        Event::assertDispatched(Saved::class);

        $nics = Nic::all();

        $this->assertEquals(1, $nics->count());
    }

    public function testIPUnavailableFails()
    {
        Event::fake([Saving::class, Saved::class]);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/vpc-test/instance/i-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'nics' => [
                        [
                            'macAddress' => 'AA:BB:CC:DD:EE:FF'
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/tier-1s/' . $this->router()->id . '/segments/' . $this->network()->id . '/dhcp-static-binding-configs?cursor=')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });

        $this->network()->subnet = '10.0.0.0/29';
        $this->network()->save();

        factory(Nic::class)->create([
            'id' => 'nic-test1',
            'ip_address' => '10.0.0.4',
            'network_id' => $this->network()->id,
        ]);

        factory(Nic::class)->create([
            'id' => 'nic-test2',
            'ip_address' => '10.0.0.5',
            'network_id' => $this->network()->id,
        ]);

        factory(Nic::class)->create([
            'id' => 'nic-test3',
            'ip_address' => '10.0.0.6',
            'network_id' => $this->network()->id,
        ]);

        $failing = false;
        Queue::failing(function(JobFailed $event) use (&$failing) {
            $failing = true;
        });

        dispatch(new ConfigureNics($this->instance()));


        $this->assertTrue($failing);
    }
}
