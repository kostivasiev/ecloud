<?php

namespace Tests\unit\Credential;

use App\Models\V2\Credential;
use Tests\TestCase;

class NullPasswordDecryptTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testDecryptNullValue()
    {
        $credential = new Credential();
        $this->assertEquals('', $credential->getPasswordAttribute(''));
        $this->assertNull($credential->getPasswordAttribute(null));
    }

    public function testDecryptNonNullValue()
    {
        $credential = new Credential();
        $this->assertEquals('somepassword', $credential->getPasswordAttribute(encrypt('somepassword')));
    }

}