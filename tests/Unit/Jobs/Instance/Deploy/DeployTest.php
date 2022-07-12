<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\Deploy;
use App\Models\V2\Task;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeployTest extends TestCase
{
    private Task $task;

    public function setUp(): void
    {
        parent::setUp();

        $this->task = $this->createSyncUpdateTask($this->instanceModel());
    }

    public function testPasses()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/fromtemplate',
                [
                    'json' => [
                        'templateName' => $this->image()->vm_template,
                        'instanceId' => $this->instanceModel()->id,
                        'numCPU' => $this->instanceModel()->vcpu_cores,
                        'ramMib' => $this->instanceModel()->ram_capacity,
                        'backupEnabled' => false,
                        'hostGroupId' => $this->sharedHostGroup()->id,

                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'nics' => [
                        [
                            'macAddress' => 'AA:BB:CC:DD:EE:FF'
                        ]
                    ]
                ]));
            });

        dispatch(new Deploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
