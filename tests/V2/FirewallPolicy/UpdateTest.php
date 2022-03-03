<?php

namespace Tests\V2\FirewallPolicy;

use App\Events\V2\Task\Created;
use App\Models\V2\FirewallPolicy;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected FirewallPolicy $policy;
    protected array $oldData;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        $this->oldData = [
            'name' => 'Demo Firewall Policy 1',
            'router_id' => $this->router()->id,
        ];
        $this->policy = FirewallPolicy::factory()->create($this->oldData)->first();
        Event::fake([Created::class]);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Updated Firewall Policy 1',
        ];
        $patch = $this->patch(
            '/v2/firewall-policies/' . $this->policy->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);

        $firewallPolicy = FirewallPolicy::findOrFail((json_decode($patch->getContent()))->data->id);
        $this->assertEquals($data['name'], $firewallPolicy->name);
        $this->assertNotEquals($this->oldData['name'], $firewallPolicy->name);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

}
