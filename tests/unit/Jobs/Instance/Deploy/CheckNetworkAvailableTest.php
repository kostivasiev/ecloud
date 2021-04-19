<?php
namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\CheckNetworkAvailable;
use App\Models\V2\Instance;
use App\Models\V2\Sync;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CheckNetworkAvailableTest extends TestCase
{
    use DatabaseMigrations;

    protected $job;

    public function setUp(): void
    {
        parent::setUp();
        $this->job = \Mockery::mock(CheckNetworkAvailable::class, [$this->instance()])->makePartial();
    }

    public function testNetworkDoesNotExists()
    {
        $instance = Instance::withoutEvents(function () {
            return factory(Instance::class)->create([
                'id' => 'i-fail',
                'vpc_id' => $this->vpc()->id,
                'name' => 'Test Instance ' . uniqid(),
                'image_id' => $this->image()->id,
                'vcpu_cores' => 1,
                'ram_capacity' => 1024,
                'platform' => 'Linux',
                'availability_zone_id' => $this->availabilityZone()->id,
                'deploy_data' => [
                    'network_id' => 'net-notexists',
                    'volume_capacity' => 20,
                    'volume_iops' => 300,
                    'requires_floating_ip' => false,
                ]
            ]);
        });
        $this->job = new CheckNetworkAvailable($instance);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [App\Models\V2\Network] net-notexists');

        $this->job->handle();
    }

    public function testNetworkSyncFail()
    {
        $sync = new Sync([
            'type' => 'update',
            'completed' => true,
            'failure_reason' => 'Test Failure',
        ]);
        $this->network()->syncs()->save($sync);

        Log::shouldReceive('info')->withSomeOfArgs(get_class($this->job) . ' : Started');
        Log::shouldReceive('info')->withSomeOfArgs("Network 'net-abcdef12' in failed sync state");
        $this->assertNull($this->job->handle());
    }

    public function testNetworkSyncInProgress()
    {
        $sync = new Sync([
            'type' => 'update',
            'completed' => false,
        ]);
        $this->network()->syncs()->save($sync);

        Log::shouldReceive('info')->withSomeOfArgs(get_class($this->job) . ' : Started');
        Log::shouldReceive('warning')
            ->withSomeOfArgs('Network not in sync, retrying in ' . $this->job->backoff . ' seconds');
        $this->assertNull($this->job->handle());
    }

    public function testSuccessful()
    {
        $sync = new Sync([
            'type' => 'update',
            'completed' => true,
        ]);
        $this->network()->syncs()->save($sync);
        Log::shouldReceive('info')->withSomeOfArgs(get_class($this->job) . ' : Started');
        Log::shouldReceive('info')->withSomeOfArgs(get_class($this->job) . ' : Finished');
        $this->assertNull($this->job->handle());
    }
}