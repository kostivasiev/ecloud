<?php

namespace Tests\V1\Credits;

use Tests\V1\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test GET credits
     */
    public function testValidCollection()
    {
        $this->get('/v1/credits', $this->validReadHeaders)
            ->assertJsonFragment([
                'type' => 'ecloud_vm_encryption'
            ])
            ->assertStatus(200);
    }
}
