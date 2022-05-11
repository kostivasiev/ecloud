<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\PrepareWindowsOsUsers;
use App\Models\V2\Credential;
use App\Models\V2\Image;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PrepareWindowsOSUsersTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->credential = $this->instanceModel()->credentials()->save(
            Credential::factory()->create([
                'username' => config('instance.guest_admin_username.windows'),
            ])
        );

        $this->image()->setAttribute('platform', Image::PLATFORM_WINDOWS)->saveQuietly();
    }

    public function testPasses()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        // UKFast Support Account
        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/guest/windows/user',
                [
                    'json' => [
                        'targetUsername' => 'ukfast.support',
                        'targetPassword' => $this->credential->password,
                        'username' => config('instance.guest_admin_username.windows'),
                        'password' => $this->credential->password,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        // Logic Monitor Account
        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/guest/windows/user',
                [
                    'json' => [
                        'targetUsername' => 'lm.i-test',
                        'targetPassword' => $this->credential->password,
                        'username' => 'graphite.rack',
                        'password' => $this->credential->password,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new PrepareWindowsOsUsers($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
