<?php

namespace Tests\V2\Host;

use App\Events\V2\Task\Created;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CrudTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testIndex()
    {
        $this->host();

        $this->get('/v2/hosts')
            ->assertJsonFragment([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => 'hg-test',
            ])
            ->assertStatus(200);
    }

    public function testShow()
    {
        $this->host();

        $this->get('/v2/hosts/h-test')
            ->assertJsonFragment([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => 'hg-test',
            ])
            ->assertStatus(200);
    }

    public function testStore()
    {
        Event::fake([Created::class]);

        $data = [
            'name' => 'h-test',
            'host_group_id' => $this->hostGroup()->id,
        ];
        $this->post('/v2/hosts', $data)
            ->assertStatus(202);
        $this->assertDatabaseHas('hosts', $data, 'ecloud');
    }

    public function testStoreWithFailedHostGroup()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->hostGroup());
            $model->save();
        });

        $data = [
            'name' => 'h-test',
            'host_group_id' => $this->hostGroup()->id,
        ];
        $this->post('/v2/hosts', $data)
            ->assertJsonFragment(
                [
                    'title' => 'Validation Error',
                    'detail' => 'The specified host group id resource currently has the status of \'failed\' and cannot be used',
                ]
            )->assertStatus(422);
    }

    public function testUpdate()
    {
        Event::fake([Created::class]);
        $this->host();

        $this->patch('/v2/hosts/h-test', [
            'name' => 'new name',
        ])->assertStatus(202);
        $this->assertDatabaseHas(
            'hosts',
            [
                'id' => 'h-test',
                'name' => 'new name',
            ],
            'ecloud'
        );
    }

    public function testDestroy()
    {
        Event::fake([Created::class]);
        $this->host();

        $this->delete('/v2/hosts/h-test')
            ->assertStatus(202);
        $this->assertDatabaseHas(
            'hosts',
            [
                'id' => 'h-test',
            ],
            'ecloud'
        );
    }
}
