<?php
namespace Tests\unit\Instance;

use App\Jobs\Sync\Instance\Update;
use App\Models\V2\Sync;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class FailedNetworkInstanceDeployTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        $this->instance()->deploy_data = [
            'network_id' => $this->network()->id,
        ];
        $this->instance()->deployed = true;
        $this->instance()->saveQuietly();
    }

    public function testJobFailsOnFailedNetwork()
    {
        // Create network fail sync
        Sync::withoutEvents(function () {
            $this->nic();
            $model = new Sync([
                'id' => 'sync-deployfail',
                'completed' => true,
                'failure_reason' => 'Failed for purposes of testing',
            ]);
            $model->resource()->associate($this->network());
            $model->save();
        });

        // Create sync for instance
        $sync = Sync::withoutEvents(function () {
            $model = new Sync([
                'id' => 'sync-instance',
                'completed' => true,
            ]);
            $model->resource()->associate($this->instance());
            $model->save();
            $this->instance()->deployed = false;
            $this->instance()->save();
            return $model;
        });

        $job = new Update($sync);
        $job->tries = 1;
        $job->backoff = 0;
        $job->handle();

        $this->assertEquals(Sync::STATUS_FAILED, $this->instance()->getStatus());

        $sync = Sync::where('resource_id', '=', $this->instance()->id)->first();
        $this->assertEquals("Network 'net-abcdef12' in failed sync state", $sync->failure_reason);
    }

    public function testSuccessfulNetworkDeploys()
    {
        // Create sync for instance
        $sync = Sync::withoutEvents(function () {
            $model = new Sync([
                'id' => 'sync-instance',
                'completed' => true,
            ]);
            $model->resource()->associate($this->instance());
            $model->save();
            return $model;
        });

        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'numCPU' => $this->instance()->vcpu_cores,
                    'ramMiB' => $this->instance()->ram_capacity,
                ]));
            });

        $job = new Update($sync);
        $this->assertNull($job->handle());
    }
}