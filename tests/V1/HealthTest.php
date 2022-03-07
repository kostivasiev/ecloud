<?php

namespace Tests\V1;

class HealthTest extends TestCase
{
    /**
     * @return void
     */
    public function testHealthCheck()
    {
        $this->get('/health')->assertStatus(200);
    }

    /**
     * @return void
     */
    public function testCanPing()
    {
        $this->get('/ping')->assertSeeText('pong')->assertStatus(200);
    }
}
