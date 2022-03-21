<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\CreateLinuxAdminGroup;
use App\Models\V2\Credential;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateLinuxAdminGroupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->credential = $this->instanceModel()->credentials()->save(
            Credential::factory()->create([
                'username' => config('instance.guest_admin_username.linux'),
            ])
        );
    }

    public function testPasses()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/admingroup',
                [
                    'json' => [
                        'username' => config('instance.guest_admin_username.linux'),
                        'password' => $this->credential->password,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new CreateLinuxAdminGroup($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
