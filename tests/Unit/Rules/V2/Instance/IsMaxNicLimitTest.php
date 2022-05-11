<?php

namespace Tests\Unit\Rules\V2\Instance;

use App\Models\V2\Nic;
use App\Rules\V2\Instance\IsMaxNicLimit;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxNicLimitTest extends TestCase
{
    protected IsMaxNicLimit $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->rule = new IsMaxNicLimit();
        config(['instance.nics.max' => 2]);
    }

    public function testLimitNotReachedPasses()
    {
        $this->nic();

        $this->assertCount(1, $this->instanceModel()->nics);

        $this->assertTrue($this->rule->passes('instance_id', $this->instanceModel()->id));
    }

    public function testLimitReachedFailsFails()
    {
        $this->nic();

        // Add a second NIC to that we have reached our limit
        Nic::factory()->for($this->instanceModel())->create();

        $this->assertCount(2, $this->instanceModel()->nics);

        $this->assertFalse($this->rule->passes('instance_id', $this->instanceModel()->id));
    }
}
