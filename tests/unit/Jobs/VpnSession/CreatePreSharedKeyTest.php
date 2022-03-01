<?php

namespace Tests\unit\Jobs\VpnSession;

use App\Events\V2\Credential\Creating;
use App\Jobs\VpnSession\CreatePreSharedKey;
use App\Models\V2\Credential;
use App\Models\V2\Task;
use App\Models\V2\VpnSession;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;

class CreatePreSharedKeyTest extends TestCase
{
    use VpnSessionMock;

    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpnSession());
            $this->task->save();
        });
    }

    public function testSuccessful()
    {
        $this->assertFalse($this->vpnSession()->credentials()->where('username', 'PSK')->exists());

        dispatch(new CreatePreSharedKey($this->task));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertTrue($this->vpnSession()->credentials()->where('username', 'PSK')->exists());
    }

    public function testPskAlreadySetSkips()
    {
        Event::fake(Creating::class);

        Credential::withoutEvents(function () {
            $credential = factory(Credential::class)->create([
                'id' => 'cred-test',
                'name' => 'Pre-shared Key for VPN Session ' . $this->vpnSession()->id,
                'host' => null,
                'username' => VpnSession::CREDENTIAL_PSK_USERNAME,
                'password' => Str::random(32),
                'port' => null,
                'is_hidden' => true,
            ]);
            $this->vpnSession()->credentials()->save($credential);
        });

        dispatch(new CreatePreSharedKey($this->task));

        Event::assertNotDispatched(Creating::class);
    }
}