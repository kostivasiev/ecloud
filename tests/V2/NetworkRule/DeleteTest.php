<?php
namespace Tests\V2\NetworkRule;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected NetworkRule $networkRule;
    protected NetworkPolicy $networkPolicy;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Model::withoutEvents(function () {
            $this->networkRule = NetworkRule::factory()->make([
                'id' => 'nr-test-1',
                'name' => 'nr-test-1',
            ]);

            $this->networkRule->networkRulePorts()->create([
                'id' => 'nrp-test',
                'name' => 'nrp-test',
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555',
            ]);

            $this->networkPolicy()->networkRules()->save($this->networkRule);
        });
    }

    public function testDeleteResource()
    {
        Event::fake([\App\Events\V2\Task\Created::class]);
        $this->vpc()->advanced_networking = true;
        $this->vpc()->saveQuietly();
        $this->delete('/v2/network-rules/' . $this->networkRule->id)
            ->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }


    public function testCanNotDeleteDhcpRules()
    {
        $networkRule = Model::withoutEvents(function () {
            $networkRule = NetworkRule::factory()->make([
                'id' => 'nr-dhcp',
                'name' => 'nr-test-1',
                'type' => NetworkRule::TYPE_DHCP
            ]);

            $this->networkPolicy()->networkRules()->save($networkRule);

            return $networkRule;
        });

        $this->delete('/v2/network-rules/' . $networkRule->id)->assertStatus(403);
    }

    public function testCanNotDeleteCatchall()
    {
        $networkRule = Model::withoutEvents(function () {
            $networkRule = NetworkRule::factory()->make([
                'id' => 'nr-' . NetworkRule::TYPE_CATCHALL,
                'name' => 'nr-test-1',
                'type' => NetworkRule::TYPE_CATCHALL
            ]);

            $this->networkPolicy()->networkRules()->save($networkRule);

            return $networkRule;
        });

        $this->delete('/v2/network-rules/' . $networkRule->id)->assertStatus(403);
    }
}