<?php

namespace Tests\unit\Vpc;

use App\Models\V2\Vpc;
use App\Rules\V2\IsMaxVpcLimitReached;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class MaxVpcValidationTest extends TestCase
{
    protected IsMaxVpcLimitReached $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->rule = new IsMaxVpcLimitReached();
    }

    public function testRulePasses()
    {
        $this->assertTrue($this->rule->passes('', ''));
    }

    public function testRuleFails()
    {
        config(['defaults.vpc.max_count' => 10]);
        $counter = 1;
        factory(Vpc::class, config('defaults.vpc.max_count'))
            ->make([
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
        $this->assertFalse($this->rule->passes('', ''));
    }
}