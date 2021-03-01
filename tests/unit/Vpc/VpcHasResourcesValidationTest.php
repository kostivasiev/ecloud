<?php

namespace Tests\unit\Vpc;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Rules\V2\VpcHasResources;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class VpcHasResourcesValidationTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Vpc */
    private $vpc;
    private $validator;
    private $availability_zone;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);

        $this->validator = new VpcHasResources();
    }

    public function testNoResourcesPasses()
    {
        $this->assertTrue($this->validator->passes('', $this->vpc->id));
    }

    public function testAssignedresourcesFails()
    {
        factory(Router::class)->create([
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availability_zone->id
        ]);

        $this->assertFalse($this->validator->passes('', $this->vpc->id));
    }
}
