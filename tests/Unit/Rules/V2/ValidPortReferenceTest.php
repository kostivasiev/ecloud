<?php

namespace Tests\Unit\Rules\V2;

use App\Rules\V2\ValidPortReference;
use Tests\TestCase;

class ValidPortReferenceTest extends TestCase
{
    public function testPasses()
    {
        $rule = new ValidPortReference();
        $this->assertTrue($rule->passes('ports.0.source', '1024'));
        $this->assertTrue($rule->passes('ports.0.source', '1024,1025,1026,1027'));
        $this->assertTrue($rule->passes('ports.0.source', '1024-1027'));
    }

    public function testFails()
    {
        $rule = new ValidPortReference();
        $this->assertFalse($rule->passes('ports.0.source', ' 1024'));
        $this->assertFalse($rule->passes('ports.0.source', '1024, 1025, 1026, 1027'));
        $this->assertFalse($rule->passes('ports.0.source', '1024.1025,1026,1027'));
        $this->assertFalse($rule->passes('ports.0.source', '1024--1027'));
    }
}
