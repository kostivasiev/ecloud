<?php

namespace Tests\unit\V2;

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

    public function testPasswordIsTwelveLetters()
    {
        $this->assertTrue(strlen($this->service->generate()) == 12);
    }

    public function testPasswordHasLowercaseLetter()
    {
        $this->assertTrue((bool)preg_match('/[A-Z]/', $this->service->generate()));
    }

    public function testPasswordHasUppercaseLetter()
    {
        $this->assertTrue((bool)preg_match('/[a-z]/', $this->service->generate()));
    }

    public function testPasswordHasNumber()
    {
        $this->assertTrue((bool)preg_match('/[0-9]/', $this->service->generate()));
    }
}
