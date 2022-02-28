<?php
namespace Tests\V2\VolumeGroup;

use App\Events\V2\Task\Created;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp():void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testValidDataSucceeds()
    {
        Event::fake(Created::class);

        $data = [
            'name' => 'Unit Test Volume Group',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ];

        $this->post('/v2/volume-groups', $data)
            ->seeInDatabase(
                'volume_groups',
                $data,
                'ecloud'
            )->assertResponseStatus(202);
        Event::assertDispatched(Created::class);
    }

    public function testVpcFailureCausesFail()
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

        $data = [
            'name' => 'Unit Test Volume Group',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ];
        $this->post(
            '/v2/volume-groups',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id resource currently has the status of \'failed\' and cannot be used',
            ]
        )->assertResponseStatus(422);
    }

    public function testNotOwnedVpcIdIdIsFailed()
    {
        $this->post(
            '/v2/volume-groups',
            [
                'name' => 'Unit Test Volume Group',
                'vpc_id' => 'x',
                'availability_zone_id' => $this->availabilityZone()->id,
            ],
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified vpc id was not found',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertResponseStatus(422);
    }
}