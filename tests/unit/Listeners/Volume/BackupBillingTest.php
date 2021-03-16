<?php

namespace Tests\unit\Listeners\Volume;

use App\Models\V2\BillingMetric;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class BackupBillingTest extends TestCase
{
    use DatabaseMigrations;

    public function testResizingVolumeUpdatesBackupBillingMetric()
    {
        $this->instance()->backup_enabled = true;
        $this->instance()->save();

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume',
                [
                    'json' => [
                        'volumeId' => 'vol-test',
                        'sizeGiB' => '10',
                        'shared' => false,
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['uuid' => 'uuid-test-uuid-test-uuid-test']));
            });

        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/uuid-test-uuid-test-uuid-test/iops',
                [
                    'json' => [
                        'limit' => '300',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'capacity' => 10,
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/vpc-test/instance/i-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'volumes' => []
                ]));
            });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/attach',
                [
                    'json' => [
                        'volumeUUID' => 'uuid-test-uuid-test-uuid-test',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $volume->instances()->attach($this->instance());

        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/uuid-test-uuid-test-uuid-test/size',
                [
                    'json' => [
                        'sizeGiB' => '15',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $volume->capacity = 15;
        $volume->save();

        $sync = Sync::where('resource_id', $volume->id)->first();

        // Check that the backup billing metric is added
        $updateBackupBillingListener = \Mockery::mock(\App\Listeners\V2\Instance\UpdateBackupBilling::class)->makePartial();
        $updateBackupBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $backupMetric = BillingMetric::getActiveByKey($this->instance(), 'backup.quota');

        $this->assertNotNull($backupMetric);

        $this->assertEquals(15, $backupMetric->value);
    }
}
