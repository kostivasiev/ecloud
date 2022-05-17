<?php

namespace Tests\Unit\Rules\V2;

use App\Rules\V2\ValidPortReference;
use Tests\TestCase;

class ValidPortReferenceTest extends TestCase
{
    protected ValidPortReference $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->rule = new ValidPortReference();
    }

    public function testValidPortPasses()
    {
        $this->assertTrue(
            $this->rule->passes('', 10)
        );

        $this->assertTrue(
            $this->rule->passes('', "10")
        );
    }

    public function testInvalidPortFails()
    {
        $this->assertFalse(
            $this->rule->passes('', 'a')
        );

        $this->assertFalse(
            $this->rule->passes('', '.')
        );
    }

    public function testValidPortRangePasses()
    {
        $this->assertTrue(
            $this->rule->passes('', '80-90')
        );
    }

    public function testValidPortCsvPasses()
    {
        $this->assertTrue(
            $this->rule->passes('', '1,2,3')
        );

        $this->assertTrue(
            $this->rule->passes('', '1,2,3,4-5')
        );

        $this->assertTrue(
            $this->rule->passes('', '1, 2, 3 ,4-5')
        );
    }

    public function testInvalidPortRangeFails()
    {
        $this->assertFalse(
            $this->rule->passes('', '1-')
        );

        $this->assertFalse(
            $this->rule->passes('', '1.1')
        );
    }

    public function testInvalidPortValuesFail()
    {
        $this->assertFalse(
            $this->rule->passes('', '99999999')
        );

        $this->assertFalse(
            $this->rule->passes('', '-1')
        );

        $this->assertFalse(
            $this->rule->passes('', '1-9999999')
        );

        $this->assertFalse(
            $this->rule->passes('', '99999-9999999')
        );

        $this->assertTrue(
            $this->rule->passes('', '1-65535')
        );
    }
}