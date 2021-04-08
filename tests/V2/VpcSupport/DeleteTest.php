<?php

namespace Tests\V2\VpcSupport;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Models\V2\VpcSupport;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected $region;
    protected $vpc;
    protected $vpcSupport;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->vpcSupport = factory(VpcSupport::class)->create([
            'vpc_id' => $this->vpc()->id
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete('/v2/support/' . $this->vpcSupport->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->assertNotNull(VpcSupport::withTrashed()->findOrFail($this->vpcSupport->id)->deleted_at);
    }
}
