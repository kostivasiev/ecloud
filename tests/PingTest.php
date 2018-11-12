<?php
namespace Tests;

class PingTest extends TestCase
{
    /**
     * A basic test example.
     * @return void
     */
    public function testGet()
    {
        $response = $this->call('GET', '/ping');

        // Verify response body
        $this->assertEquals('pong', $response->getContent());

        // Verify response status code
        $this->assertEquals(200, $response->status());
    }
}
