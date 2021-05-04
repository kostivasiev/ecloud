<?php

namespace Tests\V2\HostGroup;

use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use Laravel\Lumen\Testing\DatabaseMigrations;
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
        app()->bind(HostGroup::class, function () {
            return new HostGroup([
                'id' => 'hg-test',
            ]);
        });

        $this->hostGroupJobMocks();

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

    public function testCreateWithoutAz()
    {
        app()->bind(HostGroup::class, function () {
            return new HostGroup([
                'id' => 'hg-test',
            ]);
        });

        $this->hostGroupJobMocks();

        $data = [
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'host_spec_id' => $this->hostSpec()->id,
        ];
        $this->post('/v2/host-groups', $data)
            ->seeInDatabase('host_groups', $data, 'ecloud')
            ->assertResponseStatus(202);

        $hostGroupId = (json_decode($this->response->getContent()))->data->id;
        $hostGroup = HostGroup::findOrFail($hostGroupId);
        $this->assertEquals($this->availabilityZone()->id, $hostGroup->availability_zone_id);
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
        app()->bind(HostGroup::class, function () {
            return new HostGroup([
                'id' => 'hg-test',
            ]);
        });

        $this->hostGroupJobMocks();

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
        app()->bind(HostGroup::class, function () {
            return new HostGroup([
                'id' => 'hg-test',
            ]);
        });

        $this->hostGroupJobMocks();

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
        $this->hostGroup();

        // The request fires the jobs a second time
        $this->hostGroupJobMocks();

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
        $this->hostGroup();

        // The request fires the jobs a second time
        $this->hostGroupJobMocks();

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
        /**
         * Switch out the seeInDatabase/notSeeInDatabase with assertSoftDeleted(...) when we switch to Laravel
         * @see https://laravel.com/docs/5.8/database-testing#available-assertions
         */
        $this->hostGroup();
        $this->hostGroupDestroyMocks();

        $this->delete('/v2/host-groups/hg-test')
            ->seeInDatabase(
                'host_groups',
                [
                    'id' => 'hg-test',
                ],
                'ecloud'
            )->notSeeInDatabase(
                'host_groups',
                [
                    'id' => 'hg-test',
                    'deleted_at' => null,
                ],
                'ecloud'
            )->assertResponseStatus(204);
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
