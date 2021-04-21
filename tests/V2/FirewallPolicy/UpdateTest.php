<?php

namespace Tests\V2\FirewallPolicy;

use App\Events\V2\FirewallPolicy\Saved;
use App\Models\V2\FirewallPolicy;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected FirewallPolicy $policy;
    protected array $oldData;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('patch')
            ->andReturn(
                new Response(200, [], ''),
            );

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturn(
                new Response(200, [], json_encode(['publish_status' => 'REALIZED'])),
                new Response(200, [], json_encode(['publish_status' => 'REALIZED'])),
            );

        $this->oldData = [
            'name' => 'Demo Firewall Policy 1',
            'router_id' => $this->router()->id,
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
            ->assertResponseStatus(202);

        $firewallPolicy = FirewallPolicy::findOrFail((json_decode($this->response->getContent()))->data->id);
        $this->assertEquals($data['name'], $firewallPolicy->name);
        $this->assertNotEquals($this->oldData['name'], $firewallPolicy->name);
    }

}
