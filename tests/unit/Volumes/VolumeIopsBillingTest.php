<?php

namespace Tests\unit\Volumes;

use App\Events\V2\Sync\Updated;
use App\Listeners\V2\Volume\UpdateBilling;
use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\BillingMetric;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Product;
use App\Models\V2\Volume;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class VolumeIopsBillingTest extends TestCase
{
    use DatabaseMigrations;

    public Appliance $appliance;
    public ApplianceVersion $applianceVersion;
    public UpdateBilling $updateBilling;
    public BillingMetric $billingMetric;
    public Image $image;

    public function setUp(): void
    {
        parent::setUp();
        $this->kingpinServiceMock()
            ->shouldReceive('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-abc123xyz')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/fromtemplate')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withSomeOfArgs(
                '/api/v1/vpc/vpc-test/volume',
                [
                    'json' => [
                        'volumeId' => 'vol-abc123xyz',
                        'sizeGiB' => '100',
                        'shared' => false,
                    ]
                ]
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode(['uuid' => '81ef6326-4caf-4572-94d1-b06a422659d5']));
            });
        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-abc123xyz/volume/81ef6326-4caf-4572-94d1-b06a422659d5/attach')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-abc123xyz/volume/81ef6326-4caf-4572-94d1-b06a422659d5/detach')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        // Setup Products
        foreach ([300, 600, 1200, 2500] as $iops) {
            factory(Product::class)->create([
                'product_name' => $this->availabilityZone()->id.': volume@'.$iops.'-1gb',
                'product_category' => 'eCloud',
                'product_subcategory' => 'Storage',
                'product_supplier' => 'UKFast',
                'product_active' => 'Yes',
                'product_duration_type' => 'Hour'
            ]);
        }

        // Setup Appliance
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->applianceVersion = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ]);
        $this->image = factory(Image::class)->create([
            'appliance_version_id' => $this->applianceVersion->appliance_version_uuid
        ]);

        $this->billingMetric = app()->make(BillingMetric::class);
        app()->bind(BillingMetric::class, function () {
            return $this->billingMetric;
        });
        $this->updateBilling = new UpdateBilling();
    }

    public function testDefaultIopsBilling()
    {
        $volume = factory(Volume::class)->create([
            'id' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        // Setup event and fire listener
        $event = new Updated($volume);
        $this->updateBilling->handle($event);

        // Update the billingMetric instance now it's been saved
        $this->billingMetric->refresh();
        $this->assertEquals(300, $volume->iops);
        $this->assertEquals('disk.capacity.300', $this->billingMetric->key);
        $this->assertNull($this->billingMetric->end);
    }

    public function testUnmountedVolumeWithNonDefaultIops()
    {
        $volume = factory(Volume::class)->create([
            'id' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'iops' => 600,
        ]);

        // Setup event and fire listener
        $event = new Updated($volume);
        $this->updateBilling->handle($event);

        // Update the billingMetric instance now it's been saved
        $this->billingMetric->refresh();
        $this->assertEquals(600, $volume->iops);
        $this->assertEquals('disk.capacity.600', $this->billingMetric->key);
        $this->assertNull($this->billingMetric->end);
    }

    public function testMountedVolumeWithDefaultIops()
    {
        $volume = factory(Volume::class)->create([
            'id' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        $instance = factory(Instance::class)->create([
            'id' => 'i-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
        $instance->volumes()->attach($volume);

        // Setup event and fire listener
        $event = new Updated($volume);
        $this->updateBilling->handle($event);

        // Update the billingMetric instance now it's been saved
        $this->billingMetric->refresh();
        $this->assertEquals(300, $volume->iops);
        $this->assertEquals('disk.capacity.300', $this->billingMetric->key);
        $this->assertNull($this->billingMetric->end);
    }

    public function testMountedVolumeWithNonDefaultIops()
    {
        $volume = factory(Volume::class)->create([
            'id' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'iops' => 600
        ]);

        $instance = factory(Instance::class)->create([
            'id' => 'i-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
        Instance::withoutEvents(function () use ($instance, $volume) {
            $instance->volumes()->attach($volume->id);
        });

        // Setup event and fire listener
        $event = new Updated($volume);
        $this->updateBilling->handle($event);

        // Update the billingMetric instance now it's been saved
        $this->billingMetric->refresh();
        $this->assertEquals(600, $volume->iops); // wrong - should be 600
        $this->assertEquals('disk.capacity.600', $this->billingMetric->key);
        $this->assertNull($this->billingMetric->end);
    }

    public function testMountedVolumeNewIopsExistingMetric()
    {
        $originalBilling = factory(BillingMetric::class)->create([
            'id' => 'bm-test',
            'resource_id' => 'vol-abc123xyz',
            'vpc_id' => 'vpc-test',
            'reseller_id' => '1',
            'key' => 'disk.capacity.300',
            'value' => '100',
            'start' => (string) Carbon::now(),
            'end' => null,
            'category' => 'Storage',
            'price' => null,
        ]);
        $volume = factory(Volume::class)->create([
            'id' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'iops' => 600
        ]);

        $instance = factory(Instance::class)->create([
            'id' => 'i-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
        Instance::withoutEvents(function () use ($instance, $volume) {
            $instance->volumes()->attach($volume);
        });

        // Setup event and fire listener
        $event = new Updated($volume);
        $this->updateBilling->handle($event);

        // Update the origin billingMetric now it's been ended
        $originalBilling->refresh();
        // Update the billingMetric instance now it's been saved
        $this->billingMetric->refresh();

        $this->assertEquals('disk.capacity.300', $originalBilling->key);
        $this->assertNotNull($originalBilling->end);

        $this->assertEquals($originalBilling->resource_id, $this->billingMetric->resource_id);

        $this->assertEquals('disk.capacity.600', $this->billingMetric->key);
        $this->assertNull($this->billingMetric->end);
    }

}