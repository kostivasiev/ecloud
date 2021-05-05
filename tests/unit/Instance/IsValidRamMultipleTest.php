<?php

namespace Tests\unit\Instance;

use App\Rules\V2\IsPrivateSubnet;
use App\Rules\V2\IsValidRamMultiple;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class IsValidRamMultipleTest extends TestCase
{
    protected IsValidRamMultiple $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = new IsValidRamMultiple();
    }

    public function testValidMultiples()
    {
        $this->assertTrue($this->validator->passes('', 512));
        $this->assertTrue($this->validator->passes('', 1024));
        $this->assertTrue($this->validator->passes('', 2048));
        $this->assertTrue($this->validator->passes('', 3072));
    }

    public function testInvalidMultiples()
    {
        $this->assertFalse($this->validator->passes('', 3050));
    }
}
