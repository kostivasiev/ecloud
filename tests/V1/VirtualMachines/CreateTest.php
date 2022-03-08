<?php

namespace Tests\V1\VirtualMachines;

use App\Models\V1\Datastore;
use App\Models\V1\Host;
use App\Models\V1\HostSpecification;
use App\Models\V1\Pod;
use App\Models\V1\Solution;
use App\Rules\V1\IsValidSSHPublicKey;
use App\Services\Kingpin\V1\KingpinService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;
use Tests\V1\TestCase;

class CreateTest extends TestCase
{
    /** @var Pod */
    private $pod;

    /** @var Solution */
    private $solution;

    /** @var Host */
    private $host;

    public function setUp(): void
    {
        parent::setUp();
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

    public function testHybridDeploy()
    {
        $this->markTestSkipped();
        // Load the default datastore for the solution
        $datastore = Datastore::factory()->create([
            'reseller_lun_ucs_reseller_id' => 12345,
            'reseller_lun_name' => 'MCS_PX_VV_12345_DATA_01',
        ]);

        $mockDatastore = (object) [
            'uuid' => 'Datastore-datastore-01',
            'name' => 'MCS_PX_VV_12345_DATA_01',
            'type' => 'VMFS',
            'capacity' => 1024,
            'freeSpace' => 1024,
            'uncommitted' => 0,
            'provisioned' => 0,
            'available' => 1024,
            'used' => 0,
        ];

        $this->kingpinServiceMock($this->pod())->expects('getDatastores')
            ->with($this->solution()->getKey())
            ->andReturn([$mockDatastore]);


        $this->assertEquals(128, $this->host()->getRamSpecification());



        $this->json('POST', '/v1/vms/', [
            'environment' => 'Hybrid',
            'template' => 'CentOS 7 64-bit',
            'solution_id' => '12345',
            'cpu' => 1,
            'ram' => 2,
            'hdd' => 20,
            'name' => 'Test VM'
        ], $this->validWriteHeaders)
            ->assertStatus(201);
    }

    public function testPublicDeploy()
    {
        $this->markTestSkipped();
    }

    public function testApplianceDeploy()
    {
        $this->markTestSkipped();
    }



    //    /**
//     * @runTestsInSeparateProcesses
//     */
//    public function testHybridDeploy()
//    {
//        factory(Solution::class, 1)->create([
//            'ucs_reseller_id' => 12345,
//        ]);
//
//
//        //lets get mockery...
//
//        $mockDatastore = (object) [
//            'uuid' => 'Datastore-datastore-01',
//            'name' => 'MCS_PX_VV_12345_DATA_01',
//            'type' => 'VMFS',
//            'capacity' => 1024,
//            'freeSpace' => 1024,
//            'uncommitted' => 0,
//            'provisioned' => 0,
//            'available' => 1024,
//            'used' => 0,
//        ];
//
//
//        \Mockery::mock('overload:KingpinService')
//            ->shouldReceive('getDatastores')->andReturn([
//                $mockDatastore
//            ]);
//
//        \Mockery::mock('overload:KingpinService')
//            ->shouldReceive('getDatastore')->andReturn($mockDatastore);
//
//
//
//        // test the api
//        $this->json('POST', '/v1/vms/', [
//            'environment' => 'Hybrid',
//            'template' => 'CentOS 7 64-bit',
//            'solution_id' => '12345',
//            'cpu' => 1,
//            'ram' => 2,
//            'hdd' => 20,
//        ], [
//            'X-consumer-custom-id' => '1-1',
//            'X-consumer-groups' => 'ecloud.write',
//        ]);
//
//        $this->assertStatus(201);
//    }
}
