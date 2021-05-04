<?php
namespace Tests\V2\NetworkRule;

use App\Events\V2\NetworkPolicy\Saved;
use App\Events\V2\NetworkPolicy\Saving;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function testCreateResource()
    {
        Event::fake([Saving::class, Saved::class]);

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $data = [
            'network_policy_id' => $this->networkPolicy()->id,
            'sequence' => 1,
            'source' => '10.0.1.0/32',
            'destination' => '10.0.2.0/32',
            'action' => 'ALLOW',
            'enabled' => true,
        ];
        $this->post(
            '/v2/network-rules',
            $data
        )->seeInDatabase(
            'network_rules',
            $data,
            'ecloud'
        )->assertResponseStatus(202);

        Event::assertDispatched(Saving::class);
        Event::assertDispatched(Saved::class);
    }
}
