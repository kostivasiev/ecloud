<?php

namespace Tests\V2\FirewallPolicy;

use App\Events\V2\FirewallPolicy\Saved;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('patch')
            ->andReturn(
                new Response(200, [], ''),
            );

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturn(
                new Response(200, [], json_encode(['publish_status' => 'REALIZED']))
            );
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Demo policy rule 1',
            'sequence' => 10,
            'router_id' => $this->router()->id,
        ];
        $this->post(
            '/v2/firewall-policies',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);

        $policyId = (json_decode($this->response->getContent()))->data->id;
        $firewallPolicy = FirewallPolicy::findOrFail($policyId);
        $this->assertEquals($firewallPolicy->name, $data['name']);
        $this->assertEquals($firewallPolicy->sequence, $data['sequence']);
    }

    public function testRouterFailedCausesFail()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->router());
            $model->save();
        });

        $data = [
            'name' => 'Demo policy rule 1',
            'sequence' => 10,
            'router_id' => $this->router()->id,
        ];
        $this->post(
            '/v2/firewall-policies',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified router id resource is currently in a failed state and cannot be used',
            ]
        )->assertResponseStatus(422);
    }
}
