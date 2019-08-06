<?php

namespace Tests\Domains;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\ActiveDirectoryDomain;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test for valid collection
     * @return void
     */
    public function testValidCollection()
    {
        $total = rand(1, 2);
        factory(ActiveDirectoryDomain::class, $total)->create();

        $this->get('/v1/domains', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => $total,
        ]);
    }
}
