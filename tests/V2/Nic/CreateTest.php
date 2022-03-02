<?php

namespace Tests\V2\Nic;

use App\Events\V2\Task\Created;
use App\Models\V2\Task;
use App\Support\Sync;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testValidDataSucceeds()
    {
        Event::fake([Created::class]);

        $macAddress = $this->faker->macAddress;
        $this->post(
            '/v2/nics',
            [
                'name' => 'test-nic',
                'mac_address' => $macAddress,
                'instance_id' => $this->instanceModel()->id,
                'network_id' => $this->network()->id,
            ]
        )
            ->seeInDatabase(
                'nics',
                [
                    'name' => 'test-nic',
                    'mac_address' => $macAddress,
                    'instance_id' => $this->instanceModel()->id,
                    'network_id'  => $this->network()->id,
                ],
                'ecloud'
            )
            ->assertResponseStatus(202);

        Event::assertDispatched(Created::class);
    }

    public function testInvalidMacAddressFails()
    {
        $this->post('/v2/nics', [
                'mac_address' => 'INVALID_MAC_ADDRESS',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The mac address must be a valid MAC address',
                'status' => 422,
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidInstanceIdFails()
    {
        $this->post('/v2/nics', [
                'instance_id' => 'INVALID_INSTANCE_ID',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The instance id is not a valid Instance',
                'status' => 422,
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidNetworkIdFails()
    {
        $this->post('/v2/nics', [
                'network_id' => 'INVALID_NETWORK_ID',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The network id is not a valid Network',
                'status' => 422,
            ])
            ->assertResponseStatus(422);
    }

    public function testFailedInstanceOrNetworkCausesFailure()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test-1',
                'failure_reason' => 'instance Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->instanceModel());
            $model->save();
            $model = new Task([
                'id' => 'sync-test-2',
                'failure_reason' => 'network Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->network());
            $model->save();
        });

        $this->post('/v2/nics', [
                'mac_address' => $this->faker->macAddress,
                'instance_id' => $this->instanceModel()->id,
                'network_id' => $this->network()->id,
                'ip_address' => '10.0.0.6'
            ])->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified instance id resource currently has the status of \'failed\' and cannot be used',
            ])->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified network id resource currently has the status of \'failed\' and cannot be used',
            ])->assertResponseStatus(422);
    }
}
