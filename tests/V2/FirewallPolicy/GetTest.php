<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\FirewallPolicy;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $policy;

    public function setUp(): void
    {
        parent::setUp();
        $this->policy = factory(FirewallPolicy::class)->create()->first();
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/firewall-policies',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'       => $this->policy->id,
                'name'     => $this->policy->name,
                'sequence' => $this->policy->sequence,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/firewall-policies/'.$this->policy->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'       => $this->policy->id,
                'name'     => $this->policy->name,
                'sequence' => $this->policy->sequence,
            ])
            ->assertResponseStatus(200);
    }

}
