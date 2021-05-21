<?php
namespace Tests\unit\Network;

use App\Models\V2\Network;
use App\Models\V2\Task;
use App\Rules\V2\IsResourceAvailable;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsNetworkNotFailedTest extends TestCase
{
    protected IsResourceAvailable $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->rule = new IsResourceAvailable(Network::class);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testFailedNetwork()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
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