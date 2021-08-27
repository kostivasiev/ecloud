<?php

namespace Tests\unit\Jobs\VpnSession;

use App\Events\V2\Credential\Creating;
use App\Jobs\VpnSession\CreatePreSharedKey;
use App\Models\V2\Credential;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;

class CreatePreSharedKeyTest extends TestCase
{
    use VpnSessionMock;

    public function testSuccessful()
    {
        $this->assertFalse($this->vpnSession()->credentials()->where('username', 'PSK')->exists());

        dispatch(new CreatePreSharedKey($this->vpnSession()));

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
                'username' => 'PSK',
                'password' => Str::random(32),
                'port' => null,
                'is_hidden' => false,
            ]);
            $this->vpnSession()->credentials()->save($credential);
        });

        dispatch(new CreatePreSharedKey($this->vpnSession()));

        Event::assertNotDispatched(Creating::class);
    }
}