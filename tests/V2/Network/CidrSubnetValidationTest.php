<?php

namespace Tests\V2\Network;

use App\Rules\V2\ValidCidrSubnet;
use Tests\TestCase;

class CidrSubnetValidationTest extends TestCase
{
    protected $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = new ValidCidrSubnet();
    }

    public function testInvalidSubnet()
    {
        $this->assertFalse($this->validator->passes('', 'INVALID_STRING'));
    }

    public function testValidSubnetNoMask()
    {
        $this->assertFalse($this->validator->passes('', '10.0.0.0'));
    }

    public function testValidSubnetMask()
    {
        $this->assertFalse($this->validator->passes('', '10.0.0.0/33'));
    }

    public function testValidSubnetRange()
    {
        $this->assertTrue($this->validator->passes('', '10.0.0.0/24'));
    }
}
