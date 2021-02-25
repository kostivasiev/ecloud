<?php

namespace Tests\unit\Volumes;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Rules\V2\IsMaxVolumeLimitReached;
use Illuminate\Support\Facades\Config;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxVolumeLimitReachedTest extends TestCase
{
    use DatabaseMigrations;

    protected IsMaxVolumeLimitReached $rule;
    protected $volume;
    protected int $volumeCount;

    public function setUp(): void
    {
        parent::setUp();
        // Set volume limit to 1
        Config::set('volume.instance.limit', 1);
        $this->rule = new IsMaxVolumeLimitReached();
        $this->volumeCount = 1;
        $this->volume = factory(Volume::class, 2)->create([
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id
        ])->each(function ($volume) {
            $volume->name = 'Volume ' . $this->volumeCount;
            $volume->save();
            $this->volumeCount++;
        });
    }

    public function testLimits()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        // Test with one volume limit, and then attach that volume
        $this->assertTrue($this->rule->passes('', $this->instance()->id));
        $this->instance()->volumes()->attach($this->volume[0]);
        $this->instance()->save();

        // Now assert that we're at the limit
        $this->assertFalse($this->rule->passes('', $this->instance()->id));
    }
}