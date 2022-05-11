<?php

namespace Tests\Unit\Console\Commands\Credentials;

use App\Console\Commands\Credentials\UpdatePleskCredentials;
use App\Models\V2\Credential;
use App\Services\V2\PasswordService;
use Tests\TestCase;

class UpdatePleskCredentialsTest extends TestCase
{
    protected Credential $credential;
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credential = Credential::factory()->create([
            'name' => 'plesk_admin_password',
            'resource_id' => $this->instanceModel()->id,
            'username' => 'plesk_admin_password',
            'password' => (new PasswordService())->generate(),
            'port' => 8080,
        ]);
        $this->command = \Mockery::mock(UpdatePleskCredentials::class)->makePartial();
        $this->command->allows('line')->withAnyArgs()->andReturnTrue();
        $this->command->allows('info')->withAnyArgs()->andReturnTrue();
        $this->command->allows('option')->with('test-run')->andReturnFalse();
        $this->command->allows('option')->withAnyArgs()->andReturnTrue();
    }

    public function testCommandSucceeds()
    {
        $this->command->handle();
        $this->credential->refresh();
        $this->assertEquals('Plesk Administrator', $this->credential->name);
        $this->assertEquals('admin', $this->credential->username);
    }
}
