<?php

namespace Tests\V2\Nic;

use App\Rules\V2\ValidMacAddress;
use Faker\Factory as Faker;
use Tests\TestCase;

class MacAddressValidationTest extends TestCase
{
    protected $faker;
    protected $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->validator = new ValidMacAddress();
    }

    public function testInvalidMacAddress()
    {
        $this->assertFalse($this->validator->passes('', 'INVALID_MAC_ADDRESS'));
    }

    public function testValidMacAddress()
    {
        $this->assertTrue($this->validator->passes('', $this->faker->macAddress));
        $this->assertTrue($this->validator->passes('', '3D:F2:C9:A6:B3:4F'));
    }
}
