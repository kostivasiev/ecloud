<?php

namespace Tests\unit\FirewallRule;

use App\Rules\V2\ValidIpFormatCsvString;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ValidIpFormatCsvStringTest extends TestCase
{
    use DatabaseMigrations;

    private $validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new ValidIpFormatCsvString();
    }

    public function testInvalidValidDataFails()
    {
        $this->assertFalse($this->validator->passes('', 'qwertyuiop'));
    }

    public function testIpv4Passes()
    {
        $this->assertTrue($this->validator->passes('', '10.0.0.1'));
    }

    public function testCIDRPasses()
    {
        $this->assertTrue($this->validator->passes('', '10.0.0.0/24'));
    }

    public function testRangeBoundariesPasses()
    {
        $this->assertTrue($this->validator->passes('', '10.0.0.1-10.0.0.10'));
    }

    public function testCommaSeparatedValuesPasses()
    {
        $this->assertTrue($this->validator->passes('', '10.0.0.1-10.0.0.10,10.0.0.1,10.0.0.2/24'));
    }
}
