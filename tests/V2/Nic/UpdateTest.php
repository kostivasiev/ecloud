<?php

namespace Tests\V2\Nic;

use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Support\Sync;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected $macAddress;
    protected $nic;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->macAddress = $this->faker->macAddress;

        Nic::withoutEvents(function () {
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'mac_address' => $this->macAddress,
                'instance_id' => $this->instance()->id,
                'network_id' => $this->network()->id,
            ]);
        });
    }

    public function testInvalidMacAddressFails()
    {
        $this->patch(
            '/v2/nics/' . $this->nic->id,
            [
                'mac_address' => 'INVALID_MAC_ADDRESS',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
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
        $this->patch(
            '/v2/nics/' . $this->nic->id,
            [
                'instance_id' => 'INVALID_INSTANCE_ID',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
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
        $this->patch(
            '/v2/nics/' . $this->nic->id,
            [
                'network_id' => 'INVALID_NETWORK_ID',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
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
            $model->resource()->associate($this->instance());
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

        $this->patch(
            '/v2/nics/' . $this->nic->id,
            [
                'mac_address' => $this->macAddress,
                'instance_id' => $this->instance()->id,
                'network_id' => $this->network()->id,
                'ip_address' => '10.0.0.6'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified instance id resource is currently in a failed state and cannot be used',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified network id resource is currently in a failed state and cannot be used',
            ]
        )->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        Event::fake();
        $this->patch(
            '/v2/nics/' . $this->nic->id,
            [
                'mac_address' => $this->macAddress,
                'instance_id' => $this->instance()->id,
                'network_id' => $this->network()->id,
                'ip_address' => '10.0.0.6'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase(
                'nics',
                [
                    'id' => $this->nic->id,
                    'mac_address' => $this->macAddress,
                    'instance_id' => $this->instance()->id,
                    'network_id'  => $this->network()->id,
                    'ip_address' => '10.0.0.6'
                ],
                'ecloud'
            )
            ->assertResponseStatus(202);
    }
}
