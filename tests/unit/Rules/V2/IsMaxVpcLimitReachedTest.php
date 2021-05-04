<?php

namespace Tests\unit\Rules\V2;

use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Rules\V2\IsMaxVolumeLimitReached;
use App\Rules\V2\IsMaxVpcLimitReached;
use Dotenv\Dotenv;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxVpcLimitReachedTest extends TestCase
{
    use DatabaseMigrations;

    public function testMaxLimitReachedReturnsFails()
    {
        $vpc = null;

        Model::withoutEvents(function() use (&$vpc) {
            $vpc = factory(Vpc::class)->create([
                'id' => 'vpc-test',
            ]);
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('defaults.vpc.max_count', 1);
        $rule = new IsMaxVpcLimitReached();

        // Now assert that we're at the limit
        $this->assertFalse($rule->passes('', ''));
    }

    public function testMaxLimitNotReachedPasses()
    {
        $vpc = null;

        Model::withoutEvents(function() use (&$vpc) {
            $vpc = factory(Vpc::class)->create([
                'id' => 'vpc-test',
            ]);
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('defaults.vpc.max_count', 5);
        $rule = new IsMaxVpcLimitReached();

        // Now assert that we're at the limit
        $this->assertTrue($rule->passes('', ''));
    }

    public function testBypassedResellerPasses()
    {
        $vpc = null;

        Model::withoutEvents(function() use (&$vpc) {
            $vpc = factory(Vpc::class)->create([
                'id' => 'vpc-test',
            ]);
        });

        $this->be(new Consumer(7052, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('defaults.vpc.max_count', 1);
        $rule = new IsMaxVpcLimitReached();

        // Now assert that we're at the limit
        $this->assertTrue($rule->passes('', ''));
    }
}