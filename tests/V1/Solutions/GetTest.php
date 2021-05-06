<?php

namespace Tests\V1\Solutions;

use App\Models\V1\Solution;
use App\Models\V1\Tag;
use Tests\V1\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidCollection()
    {
        $total = rand(1, 2);
        factory(Solution::class, $total)->create();

        $this->get('/v1/solutions', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => $total,
        ]);
    }

    public function testValidItem()
    {
        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
        ]);

        $this->get('/v1/solutions/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200);
    }

    public function testInvalidItem()
    {
        $this->get('/v1/solutions/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }

    public function testInvalidSolutionVmCollection()
    {
        $this->get('/v1/solutions/12345/vms', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }

    public function testValidTagCollection()
    {
        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
        ]);

        $total = rand(1, 2);
        factory(Tag::class, $total)->create([
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
        ]);

        $this->get('/v1/solutions/123/tags', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => $total,
        ]);
    }

    public function testValidTagItem()
    {
        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
        ]);

        factory(Tag::class, 1)->create([
            'metadata_key' => 'test',
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
        ]);

        $this->get('/v1/solutions/123/tags/test', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200) && $this->seeJson([
            'key' => 'test',
        ]);
    }
}
