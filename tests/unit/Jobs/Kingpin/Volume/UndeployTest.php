<?php

namespace Tests\unit\Jobs\Kingpin\Volume;

use App\Events\V2\Nic\Saved;
use App\Events\V2\Nic\Saving;
use App\Jobs\Instance\Deploy\ConfigureNics;
use App\Jobs\Kingpin\Volume\Undeploy;
use App\Models\V2\Nic;
use App\Models\V2\Volume;
use App\Rules\V2\IpAvailable;
use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\QueryException;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSucceeds()
    {
        Volume::withoutEvents(function() {
            $this->volume = factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'uuid-test-uuid-test-uuid-test',
            ]);
        });

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs(['/api/v2/vpc/vpc-test/volume/uuid-test-uuid-test-uuid-test'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new Undeploy($this->volume));

        $this->assertTrue(true);
    }

    public function testInstanceAttachedFails()
    {
        Volume::withoutEvents(function() {
            $this->volume = factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'uuid-test-uuid-test-uuid-test',
            ]);

            $this->instance()->volumes()->attach($this->volume);
        });

        $this->expectException(\Exception::class);

        dispatch(new Undeploy($this->volume));
    }
}
