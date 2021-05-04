<?php

namespace Tests\Mocks\HostGroup;

use App\Jobs\Kingpin\HostGroup\DeleteCluster;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

trait DeleteClusterJob
{
    protected $deleteCluster;

    public function deleteClusterSetup()
    {
        $this->deleteCluster = \Mockery::mock(DeleteCluster::class)->makePartial();
        $this->deleteCluster->model = $this->hostGroup();
        $this->logStarted();
    }

    public function hostGroupNotExists()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                '/api/v2/vpc/' . $this->vpc()->id . '/hostgroup/' . $this->hostGroup()->id
            )->andReturnUsing(function () {
                return new Response(404);
            });
        $this->deleteCluster->expects('fail')
            ->with(\Mockery::on(function ($argument) {
                return $argument->getMessage() === 'Failed to get HostGroup';
            }));
    }

    public function hostGroupExists()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                '/api/v2/vpc/' . $this->vpc()->id . '/hostgroup/' . $this->hostGroup()->id
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode(['id' => $this->hostGroup()->id]));
            });
    }

    public function deleteHostGroupFails()
    {
        $this->hostGroupExists();
        $this->kingpinServiceMock()
            ->expects('delete')
            ->withSomeOfArgs(
                '/api/v2/vpc/' . $this->vpc()->id . '/hostgroup/' . $this->hostGroup()->id
            )->andReturnUsing(function () {
                return new Response(404);
            });
        $this->deleteCluster->expects('fail')
            ->with(\Mockery::on(function ($argument) {
                return strpos($argument->getMessage(), 'Failed to delete') !== false;
            }));
    }

    public function deleteHostGroupSuccess()
    {
        $this->hostGroupExists();
        $this->kingpinServiceMock()
            ->expects('delete')
            ->withSomeOfArgs(
                '/api/v2/vpc/' . $this->vpc()->id . '/hostgroup/' . $this->hostGroup()->id
            )->andReturnUsing(function () {
                return new Response(200);
            });
        $this->logFinished();
    }

    public function logStarted()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return strpos($message, 'Started') !== false;
            });
    }

    public function logFinished()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return strpos($message, 'Finished') !== false;
            });
    }
}