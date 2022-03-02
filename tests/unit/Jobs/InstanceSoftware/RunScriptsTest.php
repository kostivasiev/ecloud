<?php

namespace Tests\unit\Jobs\InstanceSoftware;

use App\Jobs\InstanceSoftware\RunScripts;
use App\Models\V2\Credential;
use App\Models\V2\InstanceSoftware;
use App\Models\V2\Software;
use App\Models\V2\Task;
use App\Support\Sync;
use Database\Seeders\SoftwareSeeder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RunScriptsTest extends TestCase
{
    private Task $task;

    private Software $software;

    public function setUp(): void
    {
        parent::setUp();
        (new SoftwareSeeder())->run();

        $this->software = Software::find('soft-aaaaaaaa');

        $instanceSoftware = app()->make(InstanceSoftware::class);
        $instanceSoftware->name = $this->software->name;
        $instanceSoftware->instance()->associate($this->instanceModel());
        $instanceSoftware->software()->associate($this->software);
        $instanceSoftware->save();

        // syncSave task on instanceSoftware
        Model::withoutEvents(function () use ($instanceSoftware) {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($instanceSoftware)->save();
        });

        // Set guest admin credentials
        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instanceModel()->credentials()->save($credential);
    }

    public function testPasses()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->kingpinServiceMock()->expects('post')
            ->twice()
            ->withArgs([
                '/api/v2/vpc/' . $this->instanceModel()->vpc->id .
                '/instance/' . $this->instanceModel()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('exit 0'),
                        'username' => 'root',
                        'password' => 'somepassword'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'exitCode' => 0,
                    'output' => ''
                ]));
            });


        dispatch(new RunScripts($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testScriptInProgressReleases()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instanceModel()->vpc->id .
                '/instance/' . $this->instanceModel()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('exit 0'),
                        'username' => 'root',
                        'password' => 'somepassword'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'exitCode' => 2,
                    'output' => ''
                ]));
            });

        dispatch(new RunScripts($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testScriptFailedFails()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instanceModel()->vpc->id .
                '/instance/' . $this->instanceModel()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('exit 0'),
                        'username' => 'root',
                        'password' => 'somepassword'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'exitCode' => 1,
                    'output' => ''
                ]));
            });

        dispatch(new RunScripts($this->task));

        Event::assertDispatched(JobFailed::class);
    }
}
