<?php
namespace Tests\V2\NetworkRule;

use App\Events\V2\NetworkPolicy\Saved;
use App\Events\V2\NetworkPolicy\Saving;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkPolicy $networkPolicy;
    protected NetworkRule $networkRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Model::withoutEvents(function () {
            $this->networkRule = factory(NetworkRule::class)->make([
                'id' => 'nr-test',
                'name' => 'nr-test',
            ]);

            $this->networkPolicy()->networkRules()->save($this->networkRule);
        });
    }

    public function testUpdateResource()
    {
        Event::fake();

        $this->patch(
            '/v2/network-rules/nr-test',
            [
                'action' => 'REJECT',
            ]
        )->seeInDatabase(
            'network_rules',
            [
                'id' => 'nr-test',
                'action' => 'REJECT',
            ],
            'ecloud'
        )->assertResponseStatus(202);

        Event::assertDispatched(Saving::class);
        Event::assertDispatched(Saved::class);
    }
}