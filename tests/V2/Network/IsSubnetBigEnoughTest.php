<?php

namespace Tests\V2\Network;

use App\Rules\V2\IsSubnetBigEnough;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class IsSubnetBigEnoughTest extends TestCase
{
    protected IsSubnetBigEnough $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = new IsSubnetBigEnough();
    }

    public function testTooSmallSubnet()
    {
        $this->assertFalse($this->validator->passes('', '10.0.0.1/30'));
    }

    public function testBigEnoughSubnet()
    {
        $this->assertTrue($this->validator->passes('', '10.0.0.1/24'));
    }
}
