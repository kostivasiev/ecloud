<?php

namespace Tests\Unit\Listeners\V2\LoadBalancer;

use App\Models\V2\BillingMetric;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    use LoadBalancerMock;

    public function testBilling()
    {
        $task = Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->loadBalancer());
            return $task;
        });

        // Check that the billing metric is added
        $updateBillingListener = new \App\Listeners\V2\LoadBalancer\UpdateBilling();
        $updateBillingListener->handle(new \App\Events\V2\Task\Updated($task));

        $metric = BillingMetric::getActiveByKey($this->loadBalancer(), 'load-balancer.medium');
        $this->assertNotNull($metric);
        $this->assertEquals(1, $metric->value);
    }
}
