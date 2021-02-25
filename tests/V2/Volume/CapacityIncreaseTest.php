<?php

namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use App\Rules\V2\VolumeCapacityIsGreater;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CapacityIncreaseTest extends TestCase
{
    use DatabaseMigrations;

    protected $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc()->id
        ]);

        $this->kingpinServiceMock()->shouldReceive('put')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id . '/volume/'.
                        $this->volume->vmware_uuid.'/size'])
            ->andReturn(
                new Response(200)
            );
    }

    public function testIncreaseSize()
    {
        $this->patch(
            '/v2/volumes/'.$this->volume->id,
            [
                'capacity' => 200,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )->assertResponseStatus(200);
    }

    public function testValidationRule()
    {
        $rule = \Mockery::mock(VolumeCapacityIsGreater::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $rule->volume = $this->volume;

        // Test with a valid value (greater than the original)
        $this->assertTrue($rule->passes('capacity', 200));

        // Test with an invalid value (less than the original)
        $this->assertFalse($rule->passes('capacity', 10));
    }

}
