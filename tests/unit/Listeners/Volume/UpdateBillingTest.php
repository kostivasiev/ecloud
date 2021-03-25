<?php
namespace Tests\unit\Listeners\Instance;

use App\Listeners\V2\Instance\ComputeChange;
use App\Models\V2\BillingMetric;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    use DatabaseMigrations;

    private $volume;
    private $sync;

    public function setUp(): void
    {
        parent::setUp();

        Volume::withoutEvents(function() {
            $this->volume = factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'capacity' => 20,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

            $this->volume->instances()->attach($this->instance());
        });
    }

    public function testResizingVolumeAddsBillingMetric()
    {
        Sync::withoutEvents(function() {
            $this->sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
            ]);
            $this->sync->resource()->associate($this->volume);
        });

        // Check that the volume billing metric is added
        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\Volume\UpdateBilling();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        $metric = BillingMetric::where('resource_id', $this->volume->id)->first();

        $this->assertNotNull($metric);
        $this->assertStringStartsWith('disk.capacity', $metric->key);
    }
}
