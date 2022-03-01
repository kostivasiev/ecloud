<?php

namespace Tests\V2\HostGroup;

use App\Events\V2\Task\Created;
use App\Models\V2\Host;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CrudTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testIndex()
    {
        $this->hostGroup();
        $this->get('/v2/host-groups')
            ->seeJson([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => 'vpc-test',
                'availability_zone_id' => 'az-test',
                'host_spec_id' => 'hs-test',
                'windows_enabled' => true,
            ])
            ->assertResponseStatus(200);
    }

    public function testShow()
    {
        $this->hostGroup();
        $this->get('/v2/host-groups/hg-test')
            ->seeJson([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => 'vpc-test',
                'availability_zone_id' => 'az-test',
                'host_spec_id' => 'hs-test',
                'windows_enabled' => true,
            ])
            ->assertResponseStatus(200);
    }

    public function testStore()
    {
        Event::fake([Created::class]);

        $data = [
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
            'windows_enabled' => true,
        ];
        $this->post('/v2/host-groups', $data)
            ->seeInDatabase('host_groups', $data, 'ecloud')
            ->assertResponseStatus(202);
    }

    public function testInvalidAzIsFailed()
    {
        $this->vpc()->setAttribute('region_id', 'test-fail')->saveQuietly();

        $data = [
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
            'windows_enabled' => true,
        ];

        $this->post('/v2/host-groups', $data)->seeJson([
            'title' => 'Not Found',
            'detail' => 'The specified availability zone is not available to that VPC',
            'status' => 404,
            'source' => 'availability_zone_id'
        ])->assertResponseStatus(404);
    }

    public function testVpcFailedCausesFailure()
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
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
            'windows_enabled' => true,
        ];
        $this->post('/v2/host-groups', $data)
            ->seeJson(
                [
                    'title' => 'Validation Error',
                    'detail' => 'The specified vpc id resource currently has the status of \'failed\' and cannot be used',
                ]
            )->assertResponseStatus(422);
    }

    public function testStoreValidationWithEmptyHostSpecId()
    {
        $this->post('/v2/host-groups', [
            'host_spec_id' => '',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The host spec id field is required',
            'status' => 422,
        ])->assertResponseStatus(422);
    }

    public function testStoreValidationWithNonExistentHostSpecId()
    {
        $this->post('/v2/host-groups', [
            'host_spec_id' => 'hs-none-existent',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The selected host spec id is invalid',
            'status' => 422,
        ])->assertResponseStatus(422);
    }

    public function testStoreWithNoWindowsEnabledFlag()
    {
        Event::fake([Created::class]);

        $data = [
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
        ];
        $this->post('/v2/host-groups', $data)
            ->seeInDatabase('host_groups', [
                'windows_enabled' => false
            ], 'ecloud')
            ->assertResponseStatus(202);
    }

    public function testStoreWitFalseWindowsEnabledFlag()
    {
        Event::fake([Created::class]);

        $data = [
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
            'windows_enabled' => false
        ];
        $this->post('/v2/host-groups', $data)
            ->seeInDatabase('host_groups', [
                'windows_enabled' => false
            ], 'ecloud')
            ->assertResponseStatus(202);
    }

    public function testUpdate()
    {
        Event::fake([Created::class]);
        $this->hostGroup();

        $this->patch('/v2/host-groups/hg-test', [
            'name' => 'new name',
        ])->seeInDatabase(
            'host_groups',
            [
                'id' => 'hg-test',
                'name' => 'new name',
            ],
            'ecloud'
        )->assertResponseStatus(202);
    }

    public function testUpdateCantChangeHostSpecId()
    {
        Event::fake([Created::class]);
        $this->hostGroup();

        $this->patch('/v2/host-groups/hg-test', [
            'host_spec_id' => 'hs-new',
        ])->seeInDatabase(
            'host_groups',
            [
                'id' => 'hg-test',
                'host_spec_id' => 'hs-test',
            ],
            'ecloud'
        )->assertResponseStatus(202);
    }

    public function testDestroy()
    {
        Event::fake([Created::class]);
        $this->hostGroup();

        $this->delete('/v2/host-groups/hg-test')
            ->seeInDatabase(
                'host_groups',
                [
                    'id' => 'hg-test',
                ],
                'ecloud'
            )->assertResponseStatus(202);
    }

    public function testDestroyCantDeleteHostGroupWhenItHasHost()
    {
        // bind data so we can use Conjurer mocks with expected host ID
        app()->bind(Host::class, function () {
            return factory(Host::class)->make([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $this->hostGroup()->id,
            ]);
        });

        $this->hostGroup();
        $this->host()->hostGroup()->associate($this->hostGroup());
        $this->delete('/v2/host-groups/hg-test')
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Can not delete Host group with active hosts',
                'status' => 422,
            ])->assertResponseStatus(422);
    }
}
