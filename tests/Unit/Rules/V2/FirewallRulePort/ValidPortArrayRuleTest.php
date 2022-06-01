<?php

namespace Tests\Unit\Rules\V2\FirewallRulePort;

use App\Rules\V2\FirewallRulePort\ValidPortArrayRule;
use Tests\TestCase;

class ValidPortArrayRuleTest extends TestCase
{
    public ValidPortArrayRule $rule;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->rule = new ValidPortArrayRule();
    }

    public function testValidValues()
    {
        $portArray = [
            [
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '443'
            ],
            [
                'protocol' => 'TCP',
                'source' => '80',
                'destination' => '80'
            ]
        ];
        $this->assertTrue($this->rule->passes('ports', $portArray));
    }

    public function testInvalidValues()
    {
        $portArray = [
            [
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '443'
            ],
            [
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '443'
            ]
        ];
        $this->assertFalse($this->rule->passes('ports', $portArray));
    }

    public function testPortRangeClashDifferentProtocolPasses()
    {
        $portArray = [
            [
                'protocol' => 'TCP',
                'source' => '400-500',
                'destination' => '800-900'
            ],
            [
                'protocol' => 'UDP',
                'source' => '400-500',
                'destination' => '800-900'
            ]
        ];
        $this->assertTrue($this->rule->passes('ports', $portArray));
    }

    public function testPortRangeClashSameProtocolFails()
    {
        $portArray = [
            [
                'protocol' => 'TCP',
                'source' => '400-500',
                'destination' => '800-900'
            ],
            [
                'protocol' => 'TCP',
                'source' => '22',
                'destination' => '810-880'
            ]
        ];
        $this->assertFalse($this->rule->passes('ports', $portArray));
    }
}
