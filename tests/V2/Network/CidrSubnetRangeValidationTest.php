<?php

namespace Tests\V2\Network;

use App\Rules\V2\ValidCidrSubnetRange;
use Tests\TestCase;

class CidrSubnetRangeValidationTest extends TestCase
{
    protected $faker;
    protected $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = new ValidCidrSubnetRange();
    }

    public function testInvalidSubnetRange()
    {
        $this->assertFalse($this->validator->passes('', 'INVALID_STRING'));
        // IP, No mask
        $this->assertFalse($this->validator->passes('', '10.0.0.0'));
        //Invalid mask
        $this->assertFalse($this->validator->passes('', '10.0.0.0/33'));
        //Invalid mask - too small range (min we allow is /29)
        $this->assertFalse($this->validator->passes('', '10.0.0.0/30'));
    }

    public function testValidSubnetRange()
    {
        $this->assertTrue($this->validator->passes('', '10.0.0.0/24'));
    }
}
