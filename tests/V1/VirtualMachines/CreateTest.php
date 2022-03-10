<?php

namespace Tests\V1\VirtualMachines;

use App\Exceptions\V1\TemplateNotFoundException;
use App\Models\V1\Datastore;
use App\Models\V1\Host;
use App\Models\V1\HostSpecification;
use App\Models\V1\Pod;
use App\Models\V1\PodTemplate;
use App\Models\V1\Solution;
use App\Models\V1\SolutionTemplate;
use App\Rules\V1\IsValidSSHPublicKey;
use App\Services\AccountsService;
use App\Services\IntapiService;
use App\Services\Kingpin\V1\KingpinService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;
use Tests\V1\TestCase;
use Faker\Factory as Faker;

class CreateTest extends TestCase
{
    private Pod $pod;

    private Solution $solution;

    private Host $host;

    private object $mockDatastoreKingpinResponse;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->mockDatastoreKingpinResponse = (object) [
            'uuid' => 'Datastore-datastore-01',
            'name' => $this->datastore()->reseller_lun_name,
            'type' => $this->datastore()->reseller_lun_lun_type,
            'capacity' => $this->datastore()->reseller_lun_size_gb * 1024,
            'freeSpace' => ($this->datastore()->reseller_lun_size_gb - 1) * 1024,
            'uncommitted' => 0,
            'provisioned' => 0,
            'available' => ($this->datastore()->reseller_lun_size_gb - 1) * 1024,
            'used' => 1024,
        ];

        $this->kingpinServiceMock($this->pod())->allows('getDatastores')
            ->with($this->solution()->getKey())
            ->andReturn([
                $this->mockDatastoreKingpinResponse
            ]);

        $this->kingpinServiceMock($this->pod())->allows('getDatastore')
            ->with($this->solution()->getKey(), $this->datastore()->reseller_lun_name)
            ->andReturn($this->mockDatastoreKingpinResponse);

        $this->kingpinServiceMock($this->pod())->allows('getHostsForSolution')
            ->with($this->solution()->getKey(), true)
            ->andReturn([
                (object) [
                    'uuid' => $this->faker->uuid,
                    'name' => $this->faker->sentence(3),
                    'macAddress' => strtolower($this->host()->ucs_node_eth0_mac),
                    'powerStatus' => 'poweredOn',
                    'networkStatus' => 'connected',
                    'vms' => [
                        (object) [
                            'id' => 1,
                            'ramGB' => 2,
                        ],
                        (object) [
                            'id' => 2,
                            'ramGB' => 2,
                        ],
                    ],
                    'stats' => [],
                ]
            ]);

        $solutionTemplate = \Mockery::mock('alias:'.SolutionTemplate::class)->makePartial();
        $solutionTemplate->allows('withName')->andThrow(new TemplateNotFoundException);

        $podTemplate = \Mockery::mock('alias:'.PodTemplate::class)->makePartial();
        $podTemplate->allows('withFriendlyName')->andReturnUsing(function () {
            return new class() {
                public $subType = 'Base';

                public $hard_drives = [];

                public function __construct() {
                    $this->hard_drives = [
                        (object) [
                            'name' => 'Hard disk 1',
                            'capacitygb' => 20
                        ]
                    ];
                }

                public function platform() {
                    return 'Linux';
                }

                public function license() {
                    return 'SOME LICENSE';
                }
            };
        });

        $intapiService = \Mockery::mock(new IntapiService(new Client()))->makePartial();
        $intapiService->allows('request')
            ->withSomeOfArgs('/automation/create_ucs_vmware_vm')
            ->andReturnSelf();

        $intapiService->allows('getResponseData')
            ->andReturnUsing(function () {
                return (object) [
                    'result' => true,
                    'data' => (object) [
                        'server_id' => 123,
                        'server_status' => 'Completed',
                        'automation_request_id' => 123456,
                        'msg' => 'Process Scheduled to Run.',
                        'credentials' => [
                            'username' => 'root',
                            'password' => 'qwertyuiop',
                        ],
                    ]
                ];
            });

        app()->bind(IntapiService::class, function () use ($intapiService) {
            return $intapiService;
        });
    }

    public function testBurstDeployDisabled()
    {
        $this->json('POST', '/v1/vms', [
            'environment' => 'Burst',
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(403);
    }

    public function testGpuDeployDisabled()
    {
        $this->json('POST', '/v1/vms', [
            'environment' => 'GPU',
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(403);
    }

    public function testValidSSHPublicKey()
    {
        $data = [
            'ssh_keys' => [
                'ssh-rsa AAAAB3NzaC1yc2EAAAABJQAAAQEArkDXvaFaTkGCxJVBTT1wUbVOTVP08VTFwkYV/VyGfyZyNVunjzPRujSt3YpVadzMVkAnnnXNn1lxTPR8gT8pn4rPw3B/Rbb7DAI7JQzQBdL2Pxza3Tsv3EdxG5ktzXovJQp31o3bkuSrMaXUL1K8Buc+UkNsHztKyAH4OqsqfGqn0mai8yKI0d5AOCjPtFIXwBXYJWQmNrjmssxae944eQ0NXrTu9J2xIDCokbrHX9H0Rk+iPwnD+Dj5/JBSK2T1VtP4Wmf8vkQol1mgUR3b8slPzi1nU1fSQgXYazohjEcLFlqsUgYy2UPLZFoFrJCVURUa034OP98AIQtMvGJOMQ== rsa-key-20170803'
            ]
        ];

        $validator = Validator::make($data, [
            'ssh_keys' => ['nullable', 'array'],
            'ssh_keys.*' => [new IsValidSSHPublicKey()]
        ]);

        $this->assertTrue($validator->passes());
    }

    public function testInvalidSSHPublicKey()
    {
        Solution::factory(1)->create();
        $solution = Solution::query()->first();
        $data = [
            'environment' => 'Hybrid',
            'cpu' => 2,
            'ram' => 2,
            'hdd' => 20,
            'solution_id' => $solution->getKey(),
            "template" => 'CentOS 7 64-bit',
            'ssh_keys' => [
                'THIS IS AN INVALID SSH PUBLIC KEY'
            ]
        ];

        $this->json('POST', '/v1/vms', $data, $this->validWriteHeaders)
            ->assertStatus(422)
            ->assertJsonFragment(
                [
                    'title' => 'Validation Error',
                    'detail' => 'ssh_keys.0 is not a valid SSH Public key',
                    'status' => 422
                ]
            );
    }

    /**
     * Create a mock datacentre / pod
     * @return Pod|mixed
     */
    public function pod()
    {
        if (empty($this->pod)) {
            $this->pod = Pod::withoutEvents(function () {
                return Pod::factory()->create([]);
            });
        }
        return $this->pod;
    }

    public function solution()
    {
        if (empty($this->solution)) {
            $this->solution = Solution::factory()->create([
                'ucs_reseller_id' => 12345,
            ]);
        }
        return $this->solution;
    }

    public function hostSpecification()
    {
        if (empty($this->hostSpecification)) {
            // Get the Hosts (ucs_nodes) for a solution
            $this->hostSpecification = HostSpecification::factory()->create();
        }
        return $this->hostSpecification;
    }

    public function host()
    {
        if (empty($this->host)) {
            // Get the Hosts (ucs_nodes) for a solution
            $this->host = Host::factory()->create([
                'ucs_node_ucs_reseller_id' => $this->solution()->getKey(),
                'ucs_node_datacentre_id' => $this->pod()->getKey(),
                'ucs_node_specification_id' => $this->hostSpecification()->getKey(),
            ]);
        }
        return $this->host;
    }

    public function datastore()
    {
        if (empty($this->datastore)) {
            // Get the Hosts (ucs_nodes) for a solution
            $this->datastore = Datastore::factory()->create([
                'reseller_lun_ucs_reseller_id' => $this->solution()->getKey(),
                'reseller_lun_name' => 'MCS_PX_VV_12345_DATA_01',
            ]);
        }
        return $this->datastore;
    }

    public function kingpinServiceMock($pod, $environment = 'Hybrid')
    {
        if (!isset($this->kingpinServiceMock)) {
            $this->kingpinServiceMock = \Mockery::mock(new KingpinService(new Client(), $pod, $environment))->makePartial();
            app()->bind(KingpinService::class, function () {
                return $this->kingpinServiceMock;
            });
        }
        return $this->kingpinServiceMock;
    }

    public function testHybridDeployInsufficientRam()
    {
        // Assert RAM from host specification
        $this->assertEquals(128, $this->host()->getRamSpecification());

        // Assert RAM total matched that of available hosts
        $this->assertEquals(128, $this->solution()->ramTotal());

        // Assert RAM allocated from host matches host RAM assigned to VMs
        $this->assertEquals(4, $this->solution()->ramAllocated());

        // Check N+1 - N+1 requires us to reserve one node's total RAM
        $this->assertEquals(128, $this->solution()->ramReserved());

        $this->asUser()->post('/v1/vms', [
            'environment' => 'Hybrid',
            'template' => 'CentOS 7 64-bit',
            'solution_id' => $this->solution()->getKey(),
            'cpu' => 1,
            'ram' => 2,
            'hdd' => 20,
            'name' => 'Test VM'
        ])->assertStatus(403);
    }

    public function testHybridDeployInsufficientSpaceOnDatastore()
    {
        // Disable N+1 so we can test with a single host
        $this->solution()->setAttribute('ucs_reseller_nplusone_active', 'No')->save();

        $this->mockDatastoreKingpinResponse->available = 0;

        $this->asUser()->post('/v1/vms', [
            'environment' => 'Hybrid',
            'template' => 'CentOS 7 64-bit',
            'solution_id' => $this->solution()->getKey(),
            'cpu' => 1,
            'ram' => 2,
            'hdd' => 20,
            'name' => 'Test VM'
        ])->assertStatus(403);
    }

    public function testHybridDeployInsufficientSpaceMultiDisk()
    {
        // Disable N+1 so we can test with a single host
        $this->solution()->setAttribute('ucs_reseller_nplusone_active', 'No')->save();

        $this->mockDatastoreKingpinResponse->available = 999;

        $this->asUser()->post('/v1/vms', [
            'environment' => 'Hybrid',
            'template' => 'CentOS 7 64-bit',
            'solution_id' => $this->solution()->getKey(),
            'cpu' => 1,
            'ram' => 2,
            'hdd_disks' => [
                [
                    'name' => 'Hard disk 1',
                    'capacity' => 500
                ],
                [
                    'name' => 'Hard disk 2',
                    'capacity' => 500
                ]
            ],
            'name' => 'Test VM'
        ])->assertStatus(403);
    }

    public function testHybridDeployMultiDiskInvalidHddName()
    {
        // Disable N+1 so we can test with a single host
        $this->solution()->setAttribute('ucs_reseller_nplusone_active', 'No')->save();

        $this->asUser()->post('/v1/vms', [
            'environment' => 'Hybrid',
            'template' => 'CentOS 7 64-bit',
            'solution_id' => $this->solution()->getKey(),
            'cpu' => 1,
            'ram' => 2,
            'hdd_disks' => [
                [
                    'name' => 'BANANA',
                    'capacity' => 500
                ],
                [
                    'name' => 'Hard disk 2',
                    'capacity' => 500
                ]
            ],
            'name' => 'Test VM'
        ])->assertStatus(422);
    }

    public function testDeployAsAdminReturnsAutomationId()
    {
        // Disable N+1 so we can test with a single host
        $this->solution()->setAttribute('ucs_reseller_nplusone_active', 'No')->save();

        $this->asAdmin()->post('/v1/vms', [
            'environment' => 'Hybrid',
            'template' => 'CentOS 7 64-bit',
            'solution_id' => $this->solution()->getKey(),
            'cpu' => 1,
            'ram' => 2,
            'hdd' => 20,
            'name' => 'Test VM'
        ])
            ->assertHeader('X-AutomationRequestId', 123456)
            ->assertStatus(202);
    }

    public function testHybridDeployDefaultDatastore()
    {
        // Disable N+1 so we can test with a single host
        $this->solution()->setAttribute('ucs_reseller_nplusone_active', 'No')->save();

        $this->asUser()->post('/v1/vms', [
            'environment' => 'Hybrid',
            'template' => 'CentOS 7 64-bit',
            'solution_id' => $this->solution()->getKey(),
            'cpu' => 1,
            'ram' => 2,
            'hdd' => 20,
            'name' => 'Test VM'
        ])
            ->assertJsonFragment([
                'id' => 123,
                'credentials' => [
                    'username' => 'root',
                    'password' => 'qwertyuiop',
                ]
            ])
            ->assertStatus(202);
    }

    public function testHybridDeploySpecifiedDatastore()
    {
        // Disable N+1 so we can test with a single host
        $this->solution()->setAttribute('ucs_reseller_nplusone_active', 'No')->save();

        $this->asUser()->post('/v1/vms', [
            'environment' => 'Hybrid',
            'template' => 'CentOS 7 64-bit',
            'solution_id' => $this->solution()->getKey(),
            'datastore_id' => $this->datastore()->getKey(),
            'cpu' => 1,
            'ram' => 2,
            'hdd' => 20,
            'name' => 'Test VM'
        ])
            ->assertJsonFragment([
                'id' => 123,
                'credentials' => [
                    'username' => 'root',
                    'password' => 'qwertyuiop',
                ]
            ])
            ->assertStatus(202);
    }

    public function testPublicDeploy()
    {
        $this->pod()->setAttribute('ucs_datacentre_public_enabled', 'Yes')->save();

        $this->datastore()->setAttribute('reseller_lun_name', 'MCS_VV_P1_VMPUBLICSTORE_SSD_NONBACKUP')->save();

        $accountsService = \Mockery::mock(new AccountsService(new Client()))->makePartial();
        $accountsService->allows('isDemoCustomer')
            ->andReturnFalse();
        $accountsService->allows('getPaymentMethod')
            ->andReturn('Invoice');

        app()->bind(AccountsService::class, function () use ($accountsService) {
            return $accountsService;
        });

        $this->asUser()->post('/v1/vms/', [
            'environment' => 'Public',
            'pod_id' => $this->pod->getKey(),
            'template' => 'CentOS 7 64-bit',
            'cpu' => 1,
            'ram' => 2,
            'hdd' => 20,
            'name' => 'Test VM'
        ])
            ->assertJsonFragment([
                'id' => 123,
                'credentials' => [
                    'username' => 'root',
                    'password' => 'qwertyuiop',
                ]
            ])
            ->assertStatus(202);
    }

    public function testApplianceDeploy()
    {
        $this->markTestSkipped();
    }

    // These would be useful too at some point...
    public function testBurstDeploy()
    {
        $this->markTestSkipped();
    }

    public function testHighGpuDeploy()
    {
        $this->markTestSkipped();
    }

    public function testEncryptedDeploy()
    {
        $this->markTestSkipped();
    }
}
