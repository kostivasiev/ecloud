<?php

namespace Tests\V2\Volume;

use App\Events\V2\Task\Created;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use VolumeGroupMock;

    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testNotOwnedVpcIdIsFailed()
    {
        $this->post('/v2/volumes', [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id
        ], [
            'X-consumer-custom-id' => '2-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified vpc id was not found',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertResponseStatus(422);
    }

    public function testInvalidAzIsFailed()
    {
        $region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->id
        ]);

        $this->post('/v2/volumes', [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $availabilityZone->id,
            'capacity' => '1',
            'os_volume' => true,
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not Found',
            'detail' => 'The specified availability zone is not available to that VPC',
            'status' => 404,
            'source' => 'availability_zone_id'
        ])->assertResponseStatus(404);
    }

    public function testFailedVpcCausesFailure()
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

        $this->post('/v2/volumes', [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => '1',
            'os_volume' => true,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id resource is currently in a failed state and cannot be used',
            ]
        )->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        Event::fake([Created::class]);

        $this->post('/v2/volumes', [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => '1',
            'os_volume' => true,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);

        $volumeId = (json_decode($this->response->getContent()))->data->id;
        $volume = Volume::find($volumeId);
        $this->assertNotNull($volume);
    }

    public function testRegionMismatch()
    {
        $this->vpc()->setAttribute('region_id', 'test-fail')->saveQuietly();
        $this->post('/v2/volumes', [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => '1',
            'os_volume' => true,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The vpc id and availability zone id resources are not in the same region',
                'status' => 422,
                'source' => 'vpc_id',
            ]
        )->assertResponseStatus(422);
    }
}
