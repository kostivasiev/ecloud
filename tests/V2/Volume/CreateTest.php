<?php

namespace Tests\V2\Volume;

use App\Events\V2\Task\Created;
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
        ])->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'The specified vpc id was not found',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertStatus(422);
    }

    public function testInvalidAzIsFailed()
    {
        $this->vpc()->setAttribute('region_id', 'test-fail')->saveQuietly();

        $this->post('/v2/volumes', [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => '1',
            'os_volume' => true,
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment([
            'title' => 'Not Found',
            'detail' => 'The specified availability zone is not available to that VPC',
            'status' => 404,
            'source' => 'availability_zone_id'
        ])->assertStatus(404);
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
        ])->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id resource currently has the status of \'failed\' and cannot be used',
            ]
        )->assertStatus(422);
    }

    public function testValidDataSucceeds()
    {
        Event::fake([Created::class]);

        $response = $this->post('/v2/volumes', [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => '1',
            'os_volume' => true,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);

        $volumeId = (json_decode($response->getContent()))->data->id;
        $volume = Volume::find($volumeId);
        $this->assertNotNull($volume);
    }
}
