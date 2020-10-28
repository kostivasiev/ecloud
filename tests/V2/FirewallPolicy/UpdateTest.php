<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
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
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;
    protected array $oldData;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
        $this->oldData = [
            'name' => 'Demo Firewall Policy 1',
            'router_id' => $this->router->getKey(),
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
