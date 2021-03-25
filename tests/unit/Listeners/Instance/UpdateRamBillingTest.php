<?php
namespace Tests\unit\Listeners\Instance;

use App\Jobs\Sync\Instance\Update;
use App\Listeners\V2\Instance\ComputeChange;
use App\Listeners\V2\Instance\DefaultPlatform;
use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Sync;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\KingpinService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Devices\AdminClient;

class UpdateRamBillingTest extends TestCase
{
    use DatabaseMigrations;

    private $sync;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testRamChangeBilling()
    {
        $originalRamMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test2',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        // Update the instance compute values
        $this->instance()->ram_capacity = 2048;

        Sync::withoutEvents(function() {
            $this->sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
            ]);
            $this->sync->resource()->associate($this->instance());
        });

        // Check that the ram billing metric is added
        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Sync\Updated($this->sync));

        $ramMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity');
        $this->assertNotNull($ramMetric);
        $this->assertEquals(2048, $ramMetric->value);

        // Check existing metric was ended
        $originalRamMetric->refresh();

        $this->assertNotNull($originalRamMetric->end);
    }
}
