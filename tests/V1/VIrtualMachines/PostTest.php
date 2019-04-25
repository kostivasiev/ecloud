<?php

namespace Tests\VirtualMachines;

use App\Models\V1\Solution;
use App\Rules\V1\IsValidSSHPublicKey;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\VirtualMachine;

use Illuminate\Support\Facades\Validator;

class PostTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testPublicDeployDisabled()
    {
        $this->json('POST', '/v1/vms', [
            'environment' => 'Public',
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(403);
    }

    public function testBurstDeployDisabled()
    {
        $this->json('POST', '/v1/vms', [
            'environment' => 'Burst',
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(403);
    }

//    todo need to mock kingpin/intapi services
//    public function testHybridCreate()
//    {
//        $this->missingFromDatabase('servers', [
//            'servers_id' => 123,
//        ]);
//
//        factory(Solution::class, 1)->create([
//            'ucs_reseller_id' => 12345,
//        ]);
//
//        $this->json('POST', '/v1/vms/', [
//            'environment' => 'Hybrid',
//            'template' => 'CentOS 7 64-bit',
//            'solution_id' => '12345',
//            'cpu' => 1,
//            'ram' => 2,
//            'hdd' => 30,
//        ], [
//            'X-consumer-custom-id' => '1-1',
//            'X-consumer-groups' => 'ecloud.write',
//        ]);
//
//        dd($this->response->getContent());
//
//        $this->assertResponseStatus(403);
//    }



    public function testPublicCloneDisabled()
    {
        factory(VirtualMachine::class, 1)->create([
            'servers_id' => 123,
            'servers_ecloud_type' => 'Public',
        ]);

        $this->json('POST', '/v1/vms/123/clone', [

        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(403);
    }

    public function testBurstCloneDisabled()
    {
        factory(VirtualMachine::class, 1)->create([
            'servers_id' => 123,
            'servers_ecloud_type' => 'Burst',
        ]);

        $this->json('POST', '/v1/vms/123/clone', [

        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(403);
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
        factory(Solution::class, 1)->create();
        $solution = Solution::query()->first();
        $data = [
            'environment' => 'Hybrid',
            'cpu' => 2,
            'ram' => 2,
            'hdd' => 20,
            'solution_id' => $solution->getKey(),
            "template"  => 'CentOS 7 64-bit',
            'ssh_keys' => [
                'THIS IS AN INVALID SSH PUBLIC KEY'
            ]
        ];

        $this->json('POST', '/v1/vms', $data, $this->validWriteHeaders)
            ->seeStatusCode(422)
            ->seeJson(
                [
                    'title' => 'Validation Error',
                    'detail' => 'ssh_keys.0 is not a valid SSH Public key',
                    'status' => 422
                ]
            );
    }
}
