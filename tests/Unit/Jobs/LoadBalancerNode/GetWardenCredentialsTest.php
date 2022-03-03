<?php

namespace Tests\Unit\Jobs\LoadBalancerNode;

use App\Jobs\LoadBalancerNode\GetWardenCredentials;
use App\Models\V2\Task;
use App\Providers\EncryptionServiceProvider;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminClusterClient;

class GetWardenCredentialsTest extends TestCase
{
    use LoadBalancerMock;

    protected string $wardenCredential;

    public function setUp(): void
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

        app()->bind(AdminClient::class, function () {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')->andReturnSelf();
            $mock->allows('clusters')->andReturnUsing(function () {
                $clusterMock = \Mockery::mock(AdminClusterClient::class)->makePartial();
                $clusterMock->allows('get')
                    ->withAnyArgs()
                    ->andReturnUsing(function () {
                        return new Response(200, [], json_encode([
                            'data' => [
                                'warden_credentials' => $this->wardenCredential,
                            ]
                        ]));
                    });
                return $clusterMock;
            });
            return $mock;
        });
    }

    public function testGetWardenCredentials()
    {
        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->loadBalancerNode());
            $task->save();
            return $task;
        });
        $job = new GetWardenCredentials($task);
        $job->handle();
        $this->assertEquals($this->wardenCredential, decrypt($task->data['warden_credentials']));
    }
}
