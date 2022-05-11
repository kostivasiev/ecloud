<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RenameWindowsAdminUser;
use App\Models\V2\Credential;
use App\Models\V2\Image;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RenameWindowsAdminUserTest extends TestCase
{
    protected Model|Credential $credential;

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

        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/guest/windows/user/administrator/username',
                [
                    'json' => [
                        'newUsername' => 'graphite.rack',
                        'username' => 'administrator',
                        'password' => $this->credential->password,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });


        dispatch(new RenameWindowsAdminUser($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
