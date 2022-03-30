<?php

namespace Tests\Unit\Jobs\Network;

use App\Jobs\Network\CreateSystemRules;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use Tests\Unit\Jobs\LoadBalancer\DeleteClusterTest;
use UKFast\Admin\Monitoring\AdminClient;
use UKFast\Admin\Monitoring\AdminCollectorsClient;
use UKFast\Admin\Monitoring\Entities\Collector;

class CreateSystemRulesTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->network());
            $this->task->save();
        });
    }

    public function testMe()
    {
        $this->getAdminClientMock();
        dispatch(new CreateSystemRules($this->task));
    }

    /**
     * Gets AdminClient mock
     * @param bool $fails
     * @return DeleteClusterTest
     */
    private function getAdminClientMock(): CreateSystemRulesTest
    {
        app()->bind(AdminClient::class, function () {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')
                ->andReturnSelf();
            $mock->allows('collectors->getAll')
                ->andReturn([
                    new Collector([
                        'name' => 'Collector Display Name',
                        'datacentre_id' => 4,
                        'datacentre' => 'MAN4',
                        'ip_address' => '123.123.123.123',
                        'is_shared' => true,
                        'created_at' => '2020-01-01T10:30:00+00:00',
                        'updated_at' => '2020-01-01T10:30:00+00:00'
                    ])
                ]);
            return $mock;
        });
        return $this;
    }
}
