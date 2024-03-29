<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @var Region */
    private $region;

    /** @var AvailabilityZone */
    private $availabilityZone;

    /** @var Vpc */
    private $vpc;

    /** @var Router */
    private $router;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = Region::factory()->create();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);
        $this->router = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->post('/v2/dhcps', [
            'vpc_id' => $this->vpc()->id,
        ])->assertJsonFragment([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertStatus(401);
    }

    public function testNullNameIsFailed()
    {
        $this->post('/v2/dhcps', [
            'vpc_id' => '',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'The vpc id field is required',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertStatus(422);
    }

    public function testVpcFailedStateCausesFail()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->vpc());
            $model->save();
        });

        $this->post('/v2/dhcps', [
            'vpc_id' => $this->vpc()->id,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id resource currently has the status of \'failed\' and cannot be used',
            ]
        )->assertStatus(422);
    }

    public function testInvalidAzIsFailed()
    {
        $region = Region::factory()->create();
        $availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $region->id
        ]);

        $data = [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $availabilityZone->id,
        ];

        $this->post('/v2/dhcps', $data, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment([
            'title' => 'Not Found',
            'detail' => 'The specified availability zone is not available to that VPC',
            'status' => 404,
            'source' => 'availability_zone_id'
        ])->assertStatus(404);
    }
}
