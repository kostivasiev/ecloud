<?php
namespace Tests\unit\Listeners\V2\Image;

use App\Models\V2\BillingMetric;
use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use App\Models\V2\Task;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    private $sync;
    public Image $image;
    public ImageMetadata $metaData;

    public function setUp(): void
    {
        parent::setUp();

        $this->image = $this->instance()->image->replicate(['vm_template', 'script_template', 'logo_uri', 'description'])
            ->fill([
                'name' => 'Test Image',
            ]);
        $this->image->visibility = Image::VISIBILITY_PRIVATE;
        $this->image->vpc_id = $this->vpc()->id;
        $this->image->description = "Image taken from instance " . $this->instance()->id . " on " .
            Carbon::now(new \DateTimeZone(config('app.timezone')))->toDayDateTimeString();
        $this->image->save();
        $this->image->availabilityZones()->attach($this->availabilityZone());

        $this->metaData = new ImageMetadata([
            'image_id' => $this->image->id,
            'key' => 'ukfast.spec.volume.min',
            'value' => 2,
        ]);
        $this->metaData->save();
    }

    public function testStartBilling()
    {
        Model::withoutEvents(function () {
            $this->sync = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => 'image_create',
            ]);
            $this->sync->resource()->associate($this->image);
        });

        // Check that billing metric is added
        $updateImageBillingListener = new \App\Listeners\V2\Image\UpdateImageBilling();
        $updateImageBillingListener->handle(new \App\Events\V2\Task\Updated($this->sync));

        $imageMetric = BillingMetric::getActiveByKey($this->image, 'image.private');
        $this->assertNotNull($imageMetric);
        $this->assertEquals(2, $imageMetric->value);
    }

    public function testUpdateBilling()
    {
        // metrics created on deploy
        $originalImageMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test1',
            'resource_id' => $this->image->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'image.private',
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);
        $this->metaData->value = 4;
        $this->metaData->save();

        Model::withoutEvents(function () {
            $this->sync = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => 'image_create',
            ]);
            $this->sync->resource()->associate($this->image);
        });

        // Check that the image billing metric is added
        $updateImageBillingListener = new \App\Listeners\V2\Image\UpdateImageBilling();
        $updateImageBillingListener->handle(new \App\Events\V2\Task\Updated($this->sync));

        $imageMetric = BillingMetric::getActiveByKey($this->image, 'image.private');
        $this->assertNotNull($imageMetric);
        $this->assertEquals(4, $imageMetric->value);

        // Check existing metric was ended
        $originalImageMetric->refresh();

        $this->assertNotNull($originalImageMetric->end);
    }

    public function testEndBillingOnDelete()
    {
        // metrics created on deploy
        $originalImageMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test1',
            'resource_id' => $this->image->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'image.private',
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);
        $this->metaData->value = 4;
        $this->metaData->save();

        Model::withoutEvents(function () {
            $this->sync = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->sync->resource()->associate($this->image);
        });

        // Delete the image
        $this->image->delete();

        $originalImageMetric->refresh();
        $this->assertNotNull($originalImageMetric->end);
    }
}