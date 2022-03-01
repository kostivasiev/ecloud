<?php

namespace Tests\unit;

use App\Services\V2\PasswordService;
use Tests\TestCase;

class PasswordServiceTest extends TestCase
{
    protected $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = app()->make(PasswordService::class);
    }

    public function testPasswordIsTwelveLettersByDefault()
    {
        $this->assertTrue(strlen($this->service->generate()) == 12);
    }

    public function testPasswordIsSpecifiedLength()
    {
        $this->assertTrue(strlen($this->service->generate(8)) == 8);
    }

    public function testThrowsExceptionWhenLengthTooSmall()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Length must be at least 6 characters');
        $this->service->generate(4);
    }

    public function testPasswordHasLowercaseLetterByDefault()
    {
        $this->assertTrue((bool)preg_match('/[A-Z]/', $this->service->generate()));
    }

    public function testPasswordHasUppercaseLetterByDefault()
    {
        $this->assertTrue((bool)preg_match('/[a-z]/', $this->service->generate()));
    }

    public function testPasswordHasNumberByDefault()
    {
        $this->assertTrue((bool)preg_match('/[0-9]/', $this->service->generate()));
    }

    public function testPasswordHasNoSpecialCharactersByDefault()
    {
        $this->assertFalse((bool)preg_match('/['.preg_quote($this->service->specialChars).']/', $this->service->generate()));
    }

    public function testPasswordHasSpecialCharacters()
    {
        $this->service->special = true;
        $this->assertTrue((bool)preg_match('/['.preg_quote($this->service->specialChars).']/', $this->service->generate()));
    }

    public function testPasswordHasNoLowercaseCharactersWhenFalse()
    {
        $this->service->lowerCase = false;
        $this->assertFalse((bool)preg_match('/[a-z]/', $this->service->generate()));
    }

    public function testPasswordHasNoUppercaseCharactersWhenFalse()
    {
        $this->service->upperCase = false;
        $this->assertFalse((bool)preg_match('/[A-Z]/', $this->service->generate()));
    }

    public function testPasswordHasNoNumericCharactersWhenFalse()
    {
        $this->service->numeric = false;
        $this->assertFalse((bool)preg_match('/[0-9]/', $this->service->generate()));
    }
}
