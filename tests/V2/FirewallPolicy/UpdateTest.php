<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected FirewallPolicy $policy;
    protected array $oldData;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->oldData = [
            'name' => 'Demo Firewall Policy 1',
        ];
        $this->policy = factory(FirewallPolicy::class)->create($this->oldData)->first();
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Updated Firewall Policy 1',
        ];
        $this->patch(
            '/v2/firewall-policies/' . $this->policy->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $firewallPolicy = FirewallPolicy::findOrFail((json_decode($this->response->getContent()))->data->id);
        $this->assertEquals($data['name'], $firewallPolicy->name);
        $this->assertNotEquals($this->oldData['name'], $firewallPolicy->name);
    }

}
