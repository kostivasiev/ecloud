<?php

namespace Tests\unit\Network;

use App\Rules\V2\IsPrivateSubnet;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class IsPrivateSubnetTest extends TestCase
{
    use DatabaseMigrations;

    protected IsPrivateSubnet $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = new IsPrivateSubnet();
    }

    public function testPrivateSubnet()
    {
        $this->assertTrue($this->validator->passes('', '10.0.0.1/30'));
    }

    public function testPublicSubnet()
    {
        $this->assertFalse($this->validator->passes('', '208.97.176.25/24'));
        $this->assertFalse($this->validator->passes('', '1.1.1.1/24'));
    }
}
