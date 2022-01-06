<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RunImageReadinessScript;
use App\Models\V2\Credential;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RunImageReadinessScriptTest extends TestCase
{
    public function testRunsReadinessScriptCompleteSucceeds()
    {
        Event::fake([JobFailed::class]);

        $this->image()->setAttribute(
            'readiness_script',
            'TEST READINESS SCRIPT'
        )->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instance()->credentials()->save($credential);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instance()->vpc->id .
                '/instance/' . $this->instance()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('TEST READINESS SCRIPT'),
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

        dispatch(new RunImageReadinessScript($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoReadinessScriptSkips()
    {
        Event::fake([JobFailed::class]);

        $this->image()->setAttribute('readiness_script', null)->save();

        $this->kingpinServiceMock()->shouldNotReceive('post');

        dispatch(new RunImageReadinessScript($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testRunsReadinessScriptInProgressReleases()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->image()->setAttribute(
            'readiness_script',
            'TEST READINESS SCRIPT'
        )->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instance()->credentials()->save($credential);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instance()->vpc->id .
                '/instance/' . $this->instance()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('TEST READINESS SCRIPT'),
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

        dispatch(new RunImageReadinessScript($this->instance()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testRunsReadinessScriptFailedFails()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->image()->setAttribute(
            'readiness_script',
            'TEST READINESS SCRIPT'
        )->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instance()->credentials()->save($credential);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instance()->vpc->id .
                '/instance/' . $this->instance()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('TEST READINESS SCRIPT'),
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

        dispatch(new RunImageReadinessScript($this->instance()));

        Event::assertDispatched(JobFailed::class);
    }
}
