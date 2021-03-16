<?php

namespace Tests\V2\HostGroup;

use App\Models\V2\Host;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CrudTest extends TestCase
{
    use DatabaseMigrations;

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
            ])
            ->assertResponseStatus(200);
    }

    public function testStore()
    {
        $data = [
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
        ];
        $this->post('/v2/host-groups', $data)
            ->seeInDatabase('host_groups', $data, 'ecloud')
            ->assertResponseStatus(201);
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

    public function testUpdate()
    {
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
        )->assertResponseStatus(200);
    }

    public function testUpdateCantChangeHostSpecId()
    {
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
        )->assertResponseStatus(200);
    }

    public function testDestroy()
    {
        /**
         * Switch out the seeInDatabase/notSeeInDatabase with assertSoftDeleted(...) when we switch to Laravel
         * @see https://laravel.com/docs/5.8/database-testing#available-assertions
         */
        $this->hostGroup();
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
        // TODO: This is crazy, we need a better solution such as the discussed traits
        // bind data so we can use Conjurer mocks with expected host ID
        app()->bind(Host::class, function () {
            return factory(Host::class)->make([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $this->hostGroup()->id,
            ]);
        });

        // Check host doesnt already exist
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(404);
            });


        // Check whether a LAN connectivity policy exists on the UCS for the VPC
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test'])
            ->andReturnUsing(function () {
                return new Response(404);
            });

        // Create LAN Policy
        $this->conjurerServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/compute/GC-UCS-FI2-DEV-A/vpc',
                [
                    'json' => [
                        'vpcId' => 'vpc-test'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        // Check available stock
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/specification/test-host-spec/host/available'])
            ->andReturnUsing(function () {
                // Empty array means no stock available, array count indicates stock available
                return new Response(200, [], json_encode([
                    [
                        'specification' => 'DUAL-4208--32GB',
                        'name' => 'DUAL-4208--32GB',
                        'interfaces' => [
                            'name' => 'eth0',
                            'address' => '00:25:B5:C0:A0:1B',
                            'type' => 'vNIC'
                        ]
                    ]
                ]));
            });

        // Create Profile
        $this->conjurerServiceMock()->expects('post')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/host',
                [
                    'json' => [
                        'specificationName' => 'test-host-spec',
                        'hostId' => 'h-test'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                // Empty array means no stock available, array count indicates stock available
                return new Response(200, [], json_encode([
                    [
                        'specification' => 'DUAL-4208--32GB',
                        'name' => 'DUAL-4208--32GB',
                        'interfaces' => [
                            'name' => 'eth0',
                            'address' => '00:25:B5:C0:A0:1B',
                            'type' => 'vNIC'
                        ]
                    ]
                ]));
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
