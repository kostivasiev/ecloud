<?php

namespace Tests\unit;

use App\Rules\V2\ValidIpv4;
use Tests\TestCase;

class ValidIpv4Test extends TestCase
{
    private $validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new ValidIpv4();
    }

    public function testInvalidValidDataFails()
    {
        $this->assertFalse($this->validator->passes('', 'qwertyuiop'));
    }

    public function testIpv4Passes()
    {
        $this->assertTrue($this->validator->passes('', '10.0.0.1'));
    }
}
