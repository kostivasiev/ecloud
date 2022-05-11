<?php

namespace Tests\V1\VirtualMachines;

use App\Models\V1\Pod;
use App\Models\V1\VirtualMachine;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\V1\TestCase;

class ConsoleSessionTest extends TestCase
{
    public function testValidRequest()
    {
        //TODO: check
        $this->markTestSkipped('WIP');

        app()->bind('App\Services\Kingpin\V1\KingpinService', function ($k) {

        });

        $pod = factory(Pod::class)->create()->first();
        $vm = factory(VirtualMachine::class)->create()->first();
        $vm->pod = $pod;

        $consoleResource = \App\Models\V1\Pod\Resource\Console::create([
            'token' => 'XXXXXXXXXXXXXXXXXXXXXXX',
            'url' => 'https://www.testdomain.com',
            'console_url' => 'https://www.testdomain.com/console',
        ]);
        $pod->addResource($consoleResource);

        $this->get('/v1/vms/999/console-session', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }
}
