<?php

namespace Tests\unit\Jobs\LoadBalancerNode;

use App\Jobs\LoadBalancerNode\DeployInstance;
use App\Models\V2\Task;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\PasswordService;
use App\Support\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class DeployInstanceTest extends TestCase
{
    use LoadBalancerMock;

    protected string $wardenCredential;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wardenCredential = '-----BEGIN NATS USER JWT-----\n*snip*\n\n*************************************************************\n';

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            $mockEncryptionServiceProvider
                ->allows('encrypt')
                ->andReturns('WaRdEn-CrEdEnTiAl');
            $mockEncryptionServiceProvider
                ->allows('decrypt')
                ->andReturns($this->wardenCredential);
            return $mockEncryptionServiceProvider;
        });

        $passwordService = new PasswordService();
        $this->loadBalancer()->credentials()->firstOrCreate(
            ['username' => 'keepalived'],
            [
                'name' => 'keepalived',
                'host' => null,
                'password' => $passwordService->generate(8),
                'port' => null,
                'is_hidden' => true,
            ]
        );
        $this->loadBalancer()->credentials()->firstOrCreate(
            ['username' => 'ukfast_stats'],
            [
                'name' => 'haproxy stats',
                'host' => null,
                'password' => $passwordService->generate(),
                'port' => 8090,
                'is_hidden' => true,
            ]
        );
    }

    public function testDeployInstance()
    {
        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
                'data' => [
                    'warden_credentials' => encrypt($this->wardenCredential),
                ],
            ]);
            $task->resource()->associate($this->loadBalancerNode());
            $task->save();
            return $task;
        });

        Bus::fake();
        $job = new DeployInstance($task);
        $job->handle();

        $this->assertEquals($this->loadBalancerInstance()->id, $task->data['loadbalancer_instance_id']);
    }
}
