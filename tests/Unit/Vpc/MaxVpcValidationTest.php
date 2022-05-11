<?php

namespace Tests\Unit\Vpc;

use App\Http\Middleware\IsMaxVpcForCustomer;
use App\Models\V2\Vpc;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class MaxVpcValidationTest extends TestCase
{
    protected $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->rule = \Mockery::mock(IsMaxVpcForCustomer::class)->makePartial();
    }

    public function testRulePasses()
    {
        $this->assertTrue($this->rule->isWithinLimit());
    }

    public function testRuleFails()
    {
        config(['defaults.vpc.max_count' => 10]);
        $counter = 1;

        Vpc::factory(config('defaults.vpc.max_count'))->make([
            'reseller_id' => 1,
            'region_id' => $this->region()->id,
            'console_enabled' => true,
        ])
            ->each(function ($vpc) use (&$counter) {
                $vpc->id = 'vpc-test' . $counter;
                $vpc->name = 'TestVPC-' . $counter;
                $vpc->saveQuietly();
                $counter++;
            });

        $this->assertFalse($this->rule->isWithinLimit());
    }
}