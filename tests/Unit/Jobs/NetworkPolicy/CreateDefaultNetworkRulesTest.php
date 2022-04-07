<?php

namespace Tests\Unit\Jobs\NetworkPolicy;

use App\Jobs\NetworkPolicy\CreateDefaultNetworkRules;
use App\Models\V2\NetworkRule;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Unit\Jobs\Router\CreateCollectorRulesTest;
use UKFast\Admin\Monitoring\AdminClient;
use UKFast\Admin\Monitoring\AdminCollectorClient;
use UKFast\Admin\Monitoring\Entities\Collector;

class CreateDefaultNetworkRulesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSucceeds()
    {
        $this->getAdminClientMock();
        $this->networkPolicy();

        $this->assertEquals($this->networkPolicy()->networkRules()->count(), 0);

        Event::fake([JobFailed::class]);

        dispatch(new CreateDefaultNetworkRules($this->networkPolicy()));

        $this->assertEquals($this->networkPolicy()->networkRules()->count(), 4);

        $this->assertDatabaseHas('network_rules', [
            'name' => 'dhcp_ingress',
            'sequence' => 10000,
            'network_policy_id' => $this->networkPolicy()->id,
            'source' => '10.0.0.2',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true,
            'type' => NetworkRule::TYPE_DHCP
        ], 'ecloud');

        $this->assertDatabaseHas('network_rules', [
            'name' => 'dhcp_egress',
            'sequence' => 10001,
            'network_policy_id' => $this->networkPolicy()->id,
            'source' => 'ANY',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'OUT',
            'enabled' => true,
            'type' => NetworkRule::TYPE_DHCP
        ], 'ecloud');

        $this->assertDatabaseHas('network_rules', [
            'name' => NetworkRule::TYPE_CATCHALL,
            'sequence' => 20000,
            'network_policy_id' => $this->networkPolicy()->id,
            'source' => 'ANY',
            'destination' => 'ANY',
            'action' => 'REJECT',
            'direction' => 'IN_OUT',
            'enabled' => true,
            'type' => NetworkRule::TYPE_CATCHALL
        ], 'ecloud');

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCatchallRuleActionIsImplemented()
    {
        $this->getAdminClientMock();
        $this->networkPolicy();

        Event::fake([JobFailed::class]);

        dispatch(new CreateDefaultNetworkRules($this->networkPolicy(), ['catchall_rule_action' => 'ALLOW']));

        $this->assertDatabaseHas('network_rules', [
            'action' => 'ALLOW',
            'type' => NetworkRule::TYPE_CATCHALL
        ], 'ecloud');

        Event::assertNotDispatched(JobFailed::class);
    }

    /**
     * Gets AdminClient mock
     * @param bool $fails
     * @return CreateCollectorRulesTest
     */
    private function getAdminClientMock(bool $fails = false): CreateDefaultNetworkRulesTest
    {
        app()->bind(AdminClient::class, function () use ($fails) {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')
                ->andReturnSelf();
            $mock->allows('collectors')
                ->andReturnUsing(function () use ($fails) {
                    $collectorsMock = \Mockery::mock(AdminCollectorClient::class)->makePartial();
                    $collectorsMock->allows('getAll')
                        ->once()
                        ->andReturnUsing(function () use ($fails) {
                            if ($fails) {
                                return [];
                            }
                            return [
                                new Collector([
                                    'name' => 'Collector Display Name',
                                    'datacentre_id' => 4,
                                    'datacentre' => 'MAN4',
                                    'ip_address' => '123.123.123.123',
                                    'is_shared' => true,
                                    'created_at' => '2020-01-01T10:30:00+00:00',
                                    'updated_at' => '2020-01-01T10:30:00+00:00'
                                ])
                            ];
                        });
                    return $collectorsMock;
                });
            return $mock;
        });
        return $this;
    }
}
