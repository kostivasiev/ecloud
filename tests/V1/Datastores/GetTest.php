<?php

namespace Tests\Datastores;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\Datastore;
use App\Models\V1\Solution;
use App\Models\V1\Pod;

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
        $count = 2;
        factory(Datastore::class, $count)->create();

        $this->get('/v1/datastores', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => $count,
            'count' => $count,
        ]);
    }

    /**
     * Test for valid item
     * @return void
     */
//    public function testValidItem()
//    {
//        factory(Pod::class, 1)->create();
//        factory(Solution::class, 1)->create();
//        factory(Datastore::class, 1)->create([
//            'reseller_lun_id' => 123,
//        ]);
//
//        $this->get('/v1/datastores/123', [
//            'X-consumer-custom-id' => '1-1',
//            'X-consumer-groups' => 'ecloud.read',
//        ]);
//
//        echo $this->response->getContent();
//        exit(PHP_EOL);
//
//        $this->assertResponseStatus(200);
//    }

    /**
     * Test for invalid item
     * @return void
     */
    public function testInvalidItem()
    {
        $this->get('/v1/datastores/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }
}
