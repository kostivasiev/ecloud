<?php

namespace Tests\V2\FirewallPolicy;

use App\Events\V2\FirewallPolicy\Saved;
use App\Events\V2\Task\Created;
use App\Models\V2\FirewallPolicy;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
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
        $this->policy = factory(FirewallPolicy::class)->create($this->oldData)->first();
        Event::fake([Created::class]);
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

        $this->policy->refresh();
        $this->assertEquals($data['name'], $this->policy->name);
        $this->assertNotEquals($this->oldData['name'], $this->policy->name);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

}
