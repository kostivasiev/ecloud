<?php

namespace Tests\V2\Host;

use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CrudTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        // bind data so we can use Conjurer mocks with expected host ID
        app()->bind(Host::class, function () {
            return factory(Host::class)->make([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $this->hostGroup()->id,
            ]);
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testIndex()
    {
        // Check host exists, lets say it does so we dont need to mock out all the create endpoints
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->host();

        $this->get('/v2/hosts')
            ->seeJson([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => 'hg-test',
            ])
            ->assertResponseStatus(200);
    }

    public function testShow()
    {
        // Check host exists, lets say it does so we dont need to mock out all the create endpoints
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->host();

        $this->get('/v2/hosts/h-test')
            ->seeJson([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => 'hg-test',
            ])
            ->assertResponseStatus(200);
    }

    public function testStore()
    {
        $this->createHostMocks();

        $data = [
            'name' => 'h-test',
            'host_group_id' => $this->hostGroup()->id,
        ];
        $this->post('/v2/hosts', $data)
            ->seeInDatabase('hosts', $data, 'ecloud')
            ->assertResponseStatus(202);
    }

    public function testUpdate()
    {
        // Mock test host already exists, we dont need to create it
        $this->conjurerServiceMock()->expects('get')->twice()
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->host();
        $this->patch('/v2/hosts/h-test', [
            'name' => 'new name',
        ])->seeInDatabase(
            'hosts',
            [
                'id' => 'h-test',
                'name' => 'new name',
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
        // Check host exists, lets say it does so we dont need to mock out all the create endpoints
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->host();

        $this->delete('/v2/hosts/h-test')
            ->seeInDatabase(
                'hosts',
                [
                    'id' => 'h-test',
                ],
                'ecloud'
            )->notSeeInDatabase(
                'hosts',
                [
                    'id' => 'h-test',
                    'deleted_at' => null,
                ],
                'ecloud'
            )->assertResponseStatus(204);
    }


    public function createHostMocks()
    {
        /**
         * Save
         */
        // Check host doesnt already exist
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andThrow(
                new RequestException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        /**
         * CreateLanPolicy
         */
        // Check whether a LAN connectivity policy exists on the UCS for the VPC
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test'])
            ->andThrow(
                new RequestException('Not Found', new Request('GET', 'test'), new Response(404))
            );

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

        /**
         * CheckAvailableCompute
         */
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

        /**
         * CreateProfile
         */
        $this->conjurerServiceMock()->expects('post')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/' . $this->vpc()->id .'/host',
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
                    'specification' => 'DUAL-4208--32GB',
                    'name' => 'DUAL-4208--32GB',
                    'interfaces' => [
                        'name' => 'eth0',
                        'address' => '00:25:B5:C0:A0:1B',
                        'type' => 'vNIC'
                    ]
                ]));
            });

        /**
         * CreateAutoDeployRule
         */
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'specification' => 'DUAL-4208--32GB',
                    'name' => 'DUAL-4208--32GB',
                    'hardwareVersion' => 'M4',
                    'interfaces' => [
                        [
                            'name' => 'eth0',
                            'address' => '00:25:B5:C0:A0:1B',
                            'type' => 'vNIC'
                        ],
                        [
                            'name' => 'eth1',
                            'address' => '00:25:B5:C0:B0:11',
                            'type' => 'vNIC'
                        ],
                        [
                            'name' => 'eth2',
                            'address' => '00:25:B5:C0:A0:10',
                            'type' => 'vNIC'
                        ],
                    ]
                ]));
            });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id .'/hostgroup/' . $this->hostGroup()->id .'/host',
                [
                    'json' => [
                        'hostId' => 'h-test',
                        'hardwareVersion' => 'M4',
                        'macAddress' => '00:25:B5:C0:A0:1B'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });
    }
}
