<?php
namespace Tests\V2\NetworkPolicy;

use App\Events\V2\NetworkPolicy\Saved;
use App\Events\V2\NetworkPolicy\Saving;
use App\Models\V2\NetworkPolicy;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Bind data so we can use actual NSX mocks
        app()->bind(NetworkPolicy::class, function () {
            return $this->networkPolicy();
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testCreateResource()
    {
        Event::fake();

        $data = [
            'name' => 'Test Policy',
            'network_id' => $this->network()->id,
        ];
        $this->post(
            '/v2/network-policies',
            $data
        )->seeInDatabase(
            'network_policies',
            [
                'name' => 'Test Policy',
                'network_id' => $this->network()->id,
            ],
            'ecloud'
        )->assertResponseStatus(202);

        Event::assertDispatched(Saving::class);
        Event::assertDispatched(Saved::class);
    }

    public function testCreateResourceNetworkAlreadyAssigned()
    {
        Event::fake();
        $data = [
            'name' => 'Test Policy',
            'network_id' => $this->network()->id,
        ];
        factory(NetworkPolicy::class)->create(array_merge(['id' => 'np-test'], $data));
        $this->post(
            '/v2/network-policies',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'This network id already has an assigned Policy'
        ])->assertResponseStatus(422);
    }
}