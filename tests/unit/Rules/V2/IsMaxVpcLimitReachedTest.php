<?php

namespace Tests\unit\Rules\V2;

use App\Http\Middleware\IsMaxVpcForCustomer;
use App\Models\V2\Vpc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxVpcLimitReachedTest extends TestCase
{
    public function testMaxLimitReachedReturnsFails()
    {
        $vpc = null;

        Model::withoutEvents(function () use (&$vpc) {
            $vpc = factory(Vpc::class)->create([
                'id' => 'vpc-test',
            ]);
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('defaults.vpc.max_count', 1);
        $rule = \Mockery::mock(IsMaxVpcForCustomer::class)->makePartial();

        // Now assert that we're at the limit
        $this->assertFalse($rule->isWithinLimit());
    }

    public function testMaxLimitNotReachedPasses()
    {
        $vpc = null;

        Model::withoutEvents(function () use (&$vpc) {
            $vpc = factory(Vpc::class)->create([
                'id' => 'vpc-test',
            ]);
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('defaults.vpc.max_count', 5);
        $rule = \Mockery::mock(IsMaxVpcForCustomer::class)->makePartial();

        // Now assert that we're at the limit
        $this->assertTrue($rule->isWithinLimit());
    }

    public function testBypassedResellerPasses()
    {
        $vpc = null;

        Model::withoutEvents(function () use (&$vpc) {
            $vpc = factory(Vpc::class)->create([
                'id' => 'vpc-test',
            ]);
        });

        $this->be(new Consumer(7052, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('defaults.vpc.max_count', 1);
        $rule = \Mockery::mock(IsMaxVpcForCustomer::class)->makePartial();

        // Now assert that we're at the limit
        $this->assertTrue($rule->isWithinLimit());
    }
}