<?php

namespace Tests\V2\Instances;

use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class ChangeHostgroupTest extends TestCase
{

    protected HostGroup $hostGroup;

    public function setUp(): void
    {
        parent::setUp();

        $this->hostGroup = HostGroup::withoutEvents(function () {
            return factory(HostGroup::class)->create([
                'id' => 'hg-newitem',
                'name' => 'hg-newitem',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);
        });

        $this->instance()->host_group_id = $this->hostGroup()->id;
        $this->instance()->deployed = true;
        $this->instance()->saveQuietly();
    }

    public function testInstanceNotOnKingpin()
    {
        $this->kingpinServiceMock()
            ->shouldReceive('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test')
            ->andThrow(
                new ClientException(
                    'Not Found',
                    new \GuzzleHttp\Psr7\Request('GET', '/'),
                    new Response(404)
                )
            );

        $this->post(
            '/v2/instances/' . $this->instance()->id . '/host-group',
            [
                'host_group_id' => $this->hostGroup->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Request Error',
                'detail' => 'Failed to make hostgroup modifications to instance ' . $this->instance()->id,
            ]
        )->assertResponseStatus(428);
    }

    public function testEvent()
    {
        $this->kingpinServiceMock()
            ->shouldReceive('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode($this->instance()->getAttributes()));
            });

        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test/reschedule')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->post(
            '/v2/instances/' . $this->instance()->id . '/host-group',
            [
                'host_group_id' => $this->hostGroup->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);

        $taskId = (json_decode($this->response->getContent()))->data->task_id;
        $task = Task::findOrFail($taskId);
        $this->assertEquals($this->instance()->id, $task->resource_id);
    }
}