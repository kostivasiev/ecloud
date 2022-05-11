<?php

namespace Tests\Unit\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Nsx\NetworkPolicy\UndeployCheck;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UndeployCheckTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('policy/api/v1/infra/domains/default/security-policies/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [],
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new UndeployCheck($this->networkPolicy()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenStillExists()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('policy/api/v1/infra/domains/default/security-policies/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'np-test'
                        ],
                    ],
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new UndeployCheck($this->networkPolicy()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
