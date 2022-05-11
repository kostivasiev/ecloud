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
        Solution::factory($total)->create();

        $this->get('/v1/solutions', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'total' => $total,
        ])->assertStatus(200);
    }

    public function testValidItem()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123,
        ]);

        $this->get('/v1/solutions/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200);
    }

    public function testInvalidItem()
    {
        $this->get('/v1/solutions/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }

    public function testInvalidSolutionVmCollection()
    {
        $this->get('/v1/solutions/12345/vms', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }

    public function testValidTagCollection()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123,
        ]);

        $total = rand(1, 2);
        Tag::factory($total)->create([
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
        ]);

        $response =$this->get('/v1/solutions/123/tags', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'total' => $total,
        ])->assertStatus(200);
    }

    public function testValidTagItem()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123,
        ]);

        Tag::factory()->create([
            'metadata_key' => 'test',
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
        ]);

        $this->get('/v1/solutions/123/tags/test', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'key' => 'test',
        ])->assertStatus(200);
    }
}
