<?php

namespace Tests\Unit\Services;

use Tests\TestCase;

class NsxServiceTest extends TestCase
{
    public function testCsvToArray()
    {
        $this->assertEquals([
            '1',
            '2',
            '3',
            '4-5'
        ],
            $this->nsxServiceMock()->csvToArray('1, 2, 3 ,4-5')
        );
    }
}