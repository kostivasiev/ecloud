<?php
namespace Tests\V2\Instances;

use App\Listeners\V2\Instance\ComputeChange;
use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class MemoryCpuChangeTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testMemoryChangeRamCapacity()
    {
        $this->kingpinServiceMock()->expects('put')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id . '/resize')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(true));
            });

        $this->instance()->vcpu_cores = 2;
        $this->instance()->ram_capacity = 2048;
        $this->instance()->save();
    }
}
