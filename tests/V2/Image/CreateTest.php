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
            'availability_zone_ids' => [
                $this->availabilityZone()->id
            ]
        ];

        $this->post('/v2/images', $data)
            ->seeJsonStructure([
                'data' => [
                    'id',
                    'task_id'
                ]
            ])->seeInDatabase(
                'images',
                [
                    'name' => 'Test Image',
                    'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
                    'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
                    'description' => 'CentOS (Community enterprise Operating System)',
                    'script_template' => '',
                    'vm_template' => 'CentOS7 x86_64',
                    'platform' => 'Linux',
                    'active' => true,
                    'public' => true,
                ],
                'ecloud')
            ->assertResponseStatus(202);
    }

    public function testStoreNotAdminFails()
    {
        $this->post('/v2/images', [])->assertResponseStatus(401);
    }
}
