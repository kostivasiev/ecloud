<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\FirewallPolicy;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Demo policy rule 1',
            'sequence' => 10
        ];
        $this->post(
            '/v2/firewall-policies',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(201);

        $policyId = (json_decode($this->response->getContent()))->data->id;
        $firewallPolicy = FirewallPolicy::findOrFail($policyId);
        $this->assertEquals($firewallPolicy->name, $data['name']);
        $this->assertEquals($firewallPolicy->sequence, $data['sequence']);
    }

}
