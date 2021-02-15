<?php

namespace Tests\unit\Listeners\Volume;

use App\Listeners\V2\Volume\ModifyVolume;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class BackupBillingTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $region;
    protected $availabilityZone;
    protected $vpc;
    protected $instance;
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-aaaaaaaa',
            'vpc_id' => $this->vpc->getKey(),
            'capacity' => 10,
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);

        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'backup_enabled' => true,
        ]);

        $this->volume->vpc()->associate($this->vpc);
        $this->volume->instances()->attach($this->instance);

        $mockKingpinService = \Mockery::mock(new KingpinService(new Client()));
        $mockKingpinService->shouldReceive('put')->andReturn(
            new Response(200)
        );

        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });
    }

    public function testResizingVolumeUpdatesBackupBillingMetric()
    {

        $this->volume->capacity = 15;
        $this->volume->save();

        $sync = Sync::where('resource_id', $this->volume->id)->first();

        // sync set to complete by the CapacityIncrease listener
        Event::assertDispatched(\App\Events\V2\Sync\Updated::class, function ($event) use ($sync) {
            return $event->model->id === $sync->id;
        });

        $sync->refresh();

        // Check that the backup billing metric is added
        $updateBackupBillingListener = \Mockery::mock(\App\Listeners\V2\Instance\UpdateBackupBilling::class)->makePartial();
        $updateBackupBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $backupMetric = BillingMetric::getActiveByKey($this->instance, 'backup.quota');

        $this->assertNotNull($backupMetric);

        $this->assertEquals(15, $backupMetric->value);
    }
}
