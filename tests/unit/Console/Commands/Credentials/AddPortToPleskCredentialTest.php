<?php

namespace Tests\unit\Console\Commands\Credentials;

use App\Console\Commands\Credentials\AddPortToPleskCredential;
use App\Models\V2\Credential;
use Tests\TestCase;

class AddPortToPleskCredentialTest extends TestCase
{
    public $commandMock;
    public Credential $credential;

    public function setUp(): void
    {
        parent::setUp();
        $this->credential = factory(Credential::class)->create([
            'id' => 'cred-plesk',
            'name' => 'plesk_admin_password',
            'username' => 'plesk_admin_password',
            'port' => null,
        ]);
        $this->commandMock = \Mockery::mock(AddPortToPleskCredential::class)->makePartial();
        $this->commandMock->allows('info')->withAnyArgs()->andReturnTrue();
        $this->commandMock->allows('line')->withAnyArgs()->andReturnTrue();
        $this->commandMock->allows('option')->with('test-run')->andReturnFalse();
    }

    public function testUpdateCredential()
    {
        $this->commandMock->handle();
        $this->credential->refresh();
        $this->assertEquals(config('plesk.admin.port'), $this->credential->port);
    }
}
