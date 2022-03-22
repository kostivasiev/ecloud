<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\PrepareLinuxOsUsers;
use App\Models\V2\Credential;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PrepareLinuxOSUsersTest extends TestCase
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

        // graphiterack
        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user',
                [
                    'json' => [
                        'targetUsername' => 'graphiterack',
                        'targetPassword' => $this->credential->password,
                        'targetSudo' => true,
                        'username' => config('instance.guest_admin_username.linux'),
                        'password' => $this->credential->password,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        // ukfastsupport
        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user',
                [
                    'json' => [
                        'targetUsername' => 'ukfastsupport',
                        'targetPassword' => $this->credential->password,
                        'targetSudo' => true,
                        'username' => config('instance.guest_admin_username.linux'),
                        'password' => $this->credential->password,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        // logic.monitor
        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user',
                [
                    'json' => [
                        'targetUsername' => 'lm.' . $this->instanceModel()->id,
                        'targetPassword' => $this->credential->password,
                        'targetSudo' => false,
                        'username' => config('instance.guest_admin_username.linux'),
                        'password' => $this->credential->password,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });


        dispatch(new PrepareLinuxOsUsers($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
