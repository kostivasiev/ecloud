<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\FirewallPolicy;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected $policy;

    public function setUp(): void
    {
        parent::setUp();
        $this->policy = factory(FirewallPolicy::class)->create()->first();
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/firewall-policies/' . $this->policy->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $firewallPolicy = FirewallPolicy::withTrashed()->findOrFail($this->policy->getKey());
        $this->assertNotNull($firewallPolicy->deleted_at);
    }

}
