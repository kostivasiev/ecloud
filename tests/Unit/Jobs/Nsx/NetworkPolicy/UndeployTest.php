<?php

namespace Tests\Unit\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Nsx\NetworkPolicy\Undeploy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UndeployTest extends TestCase
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

    public function testPolicyDeleted()
    {
        $this->nsxServiceMock()->shouldReceive('delete')
            ->withArgs([
                'policy/api/v1/infra/domains/default/security-policies/np-test',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class, Deleted::class]);

        dispatch(new Undeploy($this->networkPolicy()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicyDeleted404Response()
    {
        $this->nsxServiceMock()->shouldReceive('delete')
            ->withArgs([
                'policy/api/v1/infra/domains/default/security-policies/np-test',
            ])
            ->andThrow(
                new RequestException(
                    'Not Found',
                    new Request('delete', ''),
                    new Response(404, [], '')
                )
            );

        Event::fake([JobFailed::class, Deleted::class]);

        dispatch(new Undeploy($this->networkPolicy()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
