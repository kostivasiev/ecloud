<?php

namespace Tests\unit\Jobs\Nsx\NetworkPolicy;

use App\Jobs\NetworkPolicy\DeleteChildResources;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteChildResourcesTest extends TestCase
{
    protected NetworkRule $networkRule;
    protected NetworkRulePort $networkRulePort;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->networkRule = NetworkRule::factory()->make([
                'id' => 'nr-test-1',
                'name' => 'nr-test-1',
            ]);

            $this->networkRulePort = $this->networkRule->networkRulePorts()->create([
                'id' => 'nr-test-2',
                'name' => 'nr-test-2',
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555',
            ]);

            $this->networkPolicy()->networkRules()->save($this->networkRule);
        });
    }

    public function testPolicyRemovedAndRulesDeleted()
    {
        Event::fake([JobFailed::class, Deleted::class]);

        dispatch(new DeleteChildResources($this->networkPolicy()));

        Event::assertNotDispatched(JobFailed::class);

        $this->networkRule->refresh();
        $this->assertNotNull($this->networkRule->deleted_at);
        $this->networkRulePort->refresh();
        $this->assertNotNull($this->networkRulePort->deleted_at);
    }
}
