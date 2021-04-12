<?php
namespace Tests\unit\Network;

use App\Models\V2\Network;
use App\Models\V2\Sync;
use App\Rules\V2\IsNetworkAvailable;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsNetworkNotFailedTest extends TestCase
{
    use DatabaseMigrations;

    protected IsNetworkAvailable $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->rule = new IsNetworkAvailable();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testFailedNetwork()
    {
        // Force failure
        Sync::withoutEvents(function () {
            $model = new Sync([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
            ]);
            $model->resource()->associate($this->network());
            $model->save();
        });
        $this->assertFalse($this->rule->passes('network_id', $this->network()->id));
    }

    public function testNonFailedNetwork()
    {
        $this->assertTrue($this->rule->passes('network_id', $this->network()->id));
    }
}