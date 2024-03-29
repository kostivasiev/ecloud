<?php

namespace Tests\V2\InstanceSoftware;

use App\Events\V2\Task\Created;
use App\Models\V2\InstanceSoftware;
use App\Models\V2\Software;
use Database\Seeders\SoftwareSeeder;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CrudTest extends TestCase
{
    public $software;

    public $instanceSoftware;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        (new SoftwareSeeder())->run();

        $this->software = Software::find('soft-aaaaaaaa');

        $this->instanceSoftware = InstanceSoftware::factory()->make([
            'name' => 'McAfee'
        ]);
        $this->instanceSoftware->instance()->associate($this->instanceModel());
        $this->instanceSoftware->software()->associate($this->software);
        $this->instanceSoftware->save();
    }

    public function testIndex()
    {
        // Assert scope returns resource for [instance] owner
        $this->get('/v2/instance-software')
            ->assertJsonFragment([
                'id' => $this->instanceSoftware->id,
                'name' => 'McAfee',
                'instance_id' => $this->instanceModel()->id,
                'software_id' => $this->software->id,
            ])
            ->assertStatus(200);

        // Assert scope does not return resource for non-owner
        $this->be(new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/instance-software')
            ->assertJsonMissing([
                'id' => $this->instanceSoftware->id,
                'name' => 'McAfee',
                'instance_id' => $this->instanceModel()->id,
                'software_id' => $this->software->id,
            ])
            ->assertStatus(200);

        // Assert scope returns resource for admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/instance-software')
            ->assertJsonFragment([
                'id' => $this->instanceSoftware->id,
                'name' => 'McAfee',
                'instance_id' => $this->instanceModel()->id,
                'software_id' => $this->software->id,
            ])
            ->assertStatus(200);
    }

    public function testShow()
    {
        // Assert scope returns resource for [instance] owner
        $this->get('/v2/instance-software/' . $this->instanceSoftware->id)
            ->assertJsonFragment([
                'id' => $this->instanceSoftware->id,
                'name' => 'McAfee',
                'instance_id' => $this->instanceModel()->id,
                'software_id' => $this->software->id,
            ])
            ->assertStatus(200);

        // Assert scope does not return resource for non-owner
        $this->be(new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/instance-software/' . $this->instanceSoftware->id)->assertStatus(404);

        // Assert scope returns resource for admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/instance-software/' . $this->instanceSoftware->id)->assertStatus(200);
    }

    public function testStore()
    {
        Event::fake(Created::class);

        $data = [
            'name' => 'test',
            'instance_id' => $this->instanceModel()->id,
            'software_id' => $this->software->id,
        ];

        // Assert not admin fails
        $this->post('/v2/instance-software', $data)->assertStatus(401);

        // Assert admin creates resource
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->post('/v2/instance-software', $data)->assertStatus(202);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testUpdate()
    {
        Event::fake(Created::class);

        $data = [
            'name' => 'Test - UPDATED',
        ];

        // Assert not admin fails
        $this->patch('/v2/instance-software/' . $this->instanceSoftware->id, $data)->assertStatus(401);

        // Assert not admin passes
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->patch('/v2/instance-software/' . $this->instanceSoftware->id, $data)->assertStatus(202);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testDestroy()
    {
        Event::fake(Created::class);

        // Not admin fails
        $this->delete('/v2/instance-software/' . $this->instanceSoftware->id)->assertStatus(401);

        // Assert admin passes
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->delete('/v2/instance-software/' . $this->instanceSoftware->id)->assertStatus(202);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_delete';
        });
    }
}
