<?php

namespace Tests\V2\Vpc;

use App\Events\V2\DhcpCreated;
use App\Events\V2\VpcCreated;
use App\Models\V2\Dhcp;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{

    use DatabaseMigrations;

    public function testNoPermsIsDenied()
    {
        $data = [
            'name' => 'Manchester DC',
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            []
        )
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNullNameDefaultsToId()
    {
        $data = [
            'name' => '',
            'region_id' => $this->region()->id,
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertResponseStatus(201);

        $virtualPrivateCloudId = (json_decode($this->response->getContent()))->data->id;

        $this->seeJson([
            'id' => $virtualPrivateCloudId,
        ]);

        $vpc = Vpc::findOrFail($virtualPrivateCloudId);
        $this->assertEquals($virtualPrivateCloudId, $vpc->name);
    }

    public function testNullRegionIsFailed()
    {
        $data = [
            'name' => 'CreateTest Name',
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The region id field is required',
                'status' => 422,
                'source' => 'region_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotScopedFails()
    {
        $data = [
            'name' => 'CreateTest Name',
            'reseller_id' => 1,
            'region_id' => $this->region()->id
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Bad Request',
                'detail' => 'Missing Reseller scope',
                'status' => 400,
            ])
            ->assertResponseStatus(400);
    }

    public function testNoAdminFailsWhenConsoleIsSet()
    {
        $data = [
            'name' => 'CreateTest Name',
            'reseller_id' => 1,
            'region_id' => $this->region()->id,
            'console_enabled' => true,
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Forbidden',
                'details' => 'Request contains invalid parameters',
                'status' => 403
            ]
        )->assertResponseStatus(403);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'CreateTest Name',
            'region_id' => $this->region()->id,
            'reseller_id' => 1,
            'console_enabled' => true,
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )
            ->seeInDatabase(
                'vpcs',
                $data,
                'ecloud'
            )
            ->assertResponseStatus(201);

        $virtualPrivateCloudId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $virtualPrivateCloudId,
        ]);
    }

    public function testCreateTriggersDhcpDispatch()
    {
        Event::fake();

        $vpc = factory(Vpc::class)->create([
            'id' => 'vpc-abc123'
        ]);

        Event::assertDispatched(\App\Events\V2\Vpc\Created::class, function ($event) use ($vpc) {
            return $event->model->id === $vpc->id;
        });

        $dhcp = factory(Dhcp::class)->create([
            'id' => 'dhcp-abc123',
            'vpc_id' => 'vpc-abc123'
        ]);

        Event::assertDispatched(\App\Events\V2\Dhcp\Created::class, function ($event) use ($dhcp) {
            return $event->model->id === $dhcp->id;
        });
    }
}
