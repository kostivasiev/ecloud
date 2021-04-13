<?php

namespace Tests\unit\Jobs\Nat;

use App\Jobs\Nat\Deploy;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Router;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployTest extends TestCase
{
    use DatabaseMigrations;

    protected Nat $nat;
    protected FloatingIp $floatingIp;

    public function setUp(): void
    {
        parent::setUp();
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
}
