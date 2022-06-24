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
            ->assertJsonFragment([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => 'vpc-test',
                'availability_zone_id' => 'az-test',
                'host_spec_id' => 'hs-test',
                'windows_enabled' => true,
            ])->assertStatus(200);
    }

    public function testShow()
    {
        $this->hostGroup();
        $this->get('/v2/host-groups/hg-test')
            ->assertJsonFragment([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => 'vpc-test',
                'availability_zone_id' => 'az-test',
                'host_spec_id' => 'hs-test',
                'windows_enabled' => true,
            ])->assertStatus(200);
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
            ->assertStatus(202);

        $this->assertDatabaseHas('host_groups', $data, 'ecloud');
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

        $this->post('/v2/host-groups', $data)
            ->assertJsonFragment([
                'title' => 'Not Found',
                'detail' => 'The specified availability zone is not available to that VPC',
                'status' => 404,
                'source' => 'availability_zone_id'
            ])->assertStatus(404);
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
            ->assertJsonFragment(
                [
                    'title' => 'Validation Error',
                    'detail' => 'The specified vpc id resource currently has the status of \'failed\' and cannot be used',
                ]
            )->assertStatus(422);
    }

    public function testStoreValidationWithEmptyHostSpecId()
    {
        $this->post('/v2/host-groups', [
            'host_spec_id' => '',
        ])->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'The host spec id field is required',
            'status' => 422,
        ])->assertStatus(422);
    }

    public function testStoreValidationWithNonExistentHostSpecId()
    {
        $this->post('/v2/host-groups', [
            'host_spec_id' => 'hs-none-existent',
        ])->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'The selected host spec id is invalid',
            'status' => 422,
        ])->assertStatus(422);
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
            ->assertStatus(202);

        $this->assertDatabaseHas('host_groups', [
            'windows_enabled' => false
        ], 'ecloud');
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
            ->assertStatus(202);

        $this->assertDatabaseHas('host_groups', [
            'windows_enabled' => false
        ], 'ecloud');
    }

    public function testUpdate()
    {
        Event::fake([Created::class]);
        $this->hostGroup();

        $this->patch('/v2/host-groups/hg-test', [
            'name' => 'new name',
        ])->assertStatus(202);
        $this->assertDatabaseHas(
            'host_groups',
            [
                'id' => 'hg-test',
                'name' => 'new name',
            ],
            'ecloud'
        );
    }

    public function testUpdateCantChangeHostSpecId()
    {
        Event::fake([Created::class]);
        $this->hostGroup();

        $this->patch('/v2/host-groups/hg-test', [
            'host_spec_id' => 'hs-new',
        ])->assertStatus(202);

        $this->assertDatabaseHas(
            'host_groups',
            [
                'id' => 'hg-test',
                'host_spec_id' => 'hs-test',
            ],
            'ecloud'
        );
    }

    public function testDestroy()
    {
        Event::fake([Created::class]);
        $this->hostGroup();

        $this->delete('/v2/host-groups/hg-test')
            ->assertStatus(202);

        $this->assertDatabaseHas(
            'host_groups',
            [
                'id' => 'hg-test',
            ],
            'ecloud'
        );
    }

    public function testDestroyCantDeleteHostGroupWhenItHasHost()
    {
        // bind data so we can use Conjurer mocks with expected host ID
        app()->bind(Host::class, function () {
            return Host::factory()->make([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $this->hostGroup()->id,
            ]);
        });

        $this->hostGroup();
        $this->host()->hostGroup()->associate($this->hostGroup());
        $this->delete('/v2/host-groups/hg-test')
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'Can not delete Host group with active hosts',
                'status' => 422,
            ])->assertStatus(422);
    }

    public function testDeleteFailsWhenSpecIsHidden()
    {
        $this->hostSpec()->setAttribute('is_hidden', true)->saveQuietly();
        $this->hostGroup();
        $response = $this->asUser()
            ->delete('/v2/host-groups/hg-test')
            ->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'This HostGroup cannot be deleted',
            ])->assertStatus(403);
    }
}
