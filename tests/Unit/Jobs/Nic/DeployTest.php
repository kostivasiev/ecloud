<?php

namespace Tests\Unit\Jobs\Nic;

use App\Jobs\Nic\Deploy;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeployTest extends TestCase
{
    public function testNicAlreadyHasMacAddressSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->nic();

        $task = $this->createSyncUpdateTask($this->nic());

        $this->kingpinServiceMock()->shouldNotReceive('post');

        dispatch(new Deploy($task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testNoMacAddressReturnedFails()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->nic()->setAttribute('mac_address', null)->save();

        $task = $this->createSyncUpdateTask($this->nic());

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id .
                '/instance/' . $this->nic()->instance->id .
                '/nic',
                [
                    'json' => [
                        'networkId' => $this->network()->id,
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new Deploy($task));

        Event::assertDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class]);

        $this->nic()->setAttribute('mac_address', null)->save();

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id .
                '/instance/' . $this->nic()->instance->id .
                '/nic',
                [
                    'json' => [
                        'networkId' => $this->network()->id,
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'macAddress' => '00:50:56:8a:eb:f2'
                ]));
            });

        $task = $this->createSyncUpdateTask($this->nic());

        dispatch(new Deploy($task));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals('00:50:56:8a:eb:f2', $this->nic()->refresh()->mac_address);
    }
}
