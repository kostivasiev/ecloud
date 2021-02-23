<?php
namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class IopsModificationTest extends TestCase
{
    use DatabaseMigrations;

    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc()->getKey()
        ]);

        $this->instance()->volumes()->save($this->volume);
    }

    public function testSetValidIopsValue()
    {
        $data = [
            'iops' => 300,
        ];
        $this->patch(
            '/v2/volumes/'.$this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'volumes',
            [
                'id' => $this->volume->getKey(),
                'iops' => $data['iops'],
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }

    public function testSetInvalidIopsValue()
    {
        $data = [
            'iops' => 200,
        ];
        $this->patch(
            '/v2/volumes/'.$this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified iops field is not a valid IOPS value (300, 600, 1200, 2500)',
            'source' => 'iops',
        ])->assertResponseStatus(422);
    }

    public function testSetIopsOnUnmountedVolume()
    {
        $this->instance()->volumes()->detach($this->volume);
        $data = [
            'iops' => 200,
        ];
        $this->patch(
            '/v2/volumes/'.$this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The Iops value can only be set on mounted volumes',
            'source' => 'iops',
        ])->assertResponseStatus(422);
    }

}
