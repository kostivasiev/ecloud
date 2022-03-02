<?php

namespace Tests\unit\Vpc;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Rules\V2\VpcHasResources;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class VpcHasResourcesValidationTest extends TestCase
{
    /** @var Vpc */
    private $vpc;
    private $validator;
    private $availability_zone;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new VpcHasResources();
    }

    public function testNoResourcesPasses()
    {
        $this->assertTrue($this->validator->passes('', $this->vpc()->id));
    }

    public function testAssignedresourcesFails()
    {
        Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        $this->assertFalse($this->validator->passes('', $this->vpc()->id));
    }
}
