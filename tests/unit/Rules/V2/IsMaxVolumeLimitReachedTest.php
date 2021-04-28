<?php

namespace Tests\unit\Rules\V2;

use App\Models\V2\Volume;
use App\Rules\V2\IsMaxVolumeLimitReached;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxVolumeLimitReachedTest extends TestCase
{
    use DatabaseMigrations;

    public function testLimits()
    {
        $volume = null;

        Volume::withoutEvents(function() use (&$volume) {
            $volume = factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id
            ]);
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('volume.instance.limit', 1);
        $rule = new IsMaxVolumeLimitReached();

        // Test with one volume limit, and then attach that volume
        $this->assertTrue($rule->passes('', $this->instance()->id));

        Model::withoutEvents(function () use ($volume) {
            $this->instance()->volumes()->attach($volume);
        });

        // Now assert that we're at the limit
        $this->assertFalse($rule->passes('', $this->instance()->id));
    }
}