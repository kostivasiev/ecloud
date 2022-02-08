<?php

namespace Tests\unit\Rules\V2;

use App\Models\V2\LoadBalancerNetwork;
use App\Rules\V2\ArePivotResourcesAvailable;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class ArePivotResourcesAvailableTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public function testResourcesAvailablePasses()
    {
        $rule = new ArePivotResourcesAvailable(
            LoadBalancerNetwork::class,
            ['network', 'loadBalancer']
        );

        $this->assertTrue(
            $rule->passes(
                'load_balancer_network_id',
                $this->loadBalancerNetwork()->id
            )
        );
    }

    public function testNetworkRelationNotAvailableFails()
    {
        $rule = new ArePivotResourcesAvailable(
            LoadBalancerNetwork::class,
            ['network', 'loadBalancer']
        );

        $this->createSyncUpdateTask($this->network())
            ->setAttribute('failure_reason', 'test')
            ->saveQuietly();

        $this->assertFalse(
            $rule->passes(
                'load_balancer_network_id',
                $this->loadBalancerNetwork()->id
            )
        );
    }

    public function testLoadBalancerRelationNotAvailableFails()
    {
        $rule = new ArePivotResourcesAvailable(
            LoadBalancerNetwork::class,
            ['network', 'loadBalancer']
        );

        $this->createSyncUpdateTask($this->loadBalancer())
            ->setAttribute('failure_reason', 'test')
            ->saveQuietly();

        $this->assertFalse(
            $rule->passes(
                'load_balancer_network_id',
                $this->loadBalancerNetwork()->id
            )
        );
    }
}
