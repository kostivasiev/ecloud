<?php

namespace Tests\V2\Image;

use App\Models\V2\Image;
use App\Models\V2\Task;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        app()->bind(Image::class, function () {
            $image = \Mockery::mock($this->networkPolicy())->makePartial();
            $image->expects('syncSave')
                ->andReturnUsing(function () use ($image) {
                    $image->save();
                    $task = app()->make(Task::class);
                    $task->id = 'test-task';
                    $task->name = $task->id;
                    $task->resource()->associate($image);
                    $task->completed = false;
                    $task->save();
                    return $task;
                });

            return $image;
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }


    public function testStoreAdminIsSuccess()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $data = [
            'name' => 'Test Image',
            'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
            'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
            'description' => 'CentOS (Community enterprise Operating System)',
            'script_template' => '',
            'vm_template' => 'CentOS7 x86_64',
            'platform' => 'Linux',
            'active' => true,
            'public' => true,
            'visibility' => Image::VISIBILITY_PUBLIC,
            'availability_zone_ids' => [
                $this->availabilityZone()->id
            ]
        ];

        $this->post('/v2/images', $data)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'task_id'
                ]
            ])->assertStatus(202);

        $this->assertDatabaseHas(
            'images',
            [
                'name' => 'Test Image',
                'logo_uri' => addslashes('https://images.ukfast.co.uk/logos/centos/300x300_white.png'),
                'documentation_uri' => addslashes('https://docs.centos.org/en-US/docs/'),
                'description' => 'CentOS (Community enterprise Operating System)',
                'script_template' => null,
                'vm_template' => 'CentOS7 x86_64',
                'platform' => 'Linux',
                'active' => 1,
                'public' => 1,
            ],
            'ecloud'
        );
    }

    public function testStoreNotAdminFails()
    {
        $this->post('/v2/images', [])->assertStatus(401);
    }
}
