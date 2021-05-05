<?php

namespace Tests\V2\VpcSupport;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Models\V2\VpcSupport;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $region;
    protected $vpc;
    protected $vpcSupport;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpcSupport = factory(VpcSupport::class)->create([
            'vpc_id' => $this->vpc()->id
        ]);
    }

    public function testValidDataIsSuccessful()
    {
        $vpc = null;
        Model::withoutEvents(function() use (&$vpc) {
            $vpc = factory(Vpc::class)->create([
                'id' => 'vpc-test2',
                'region_id' => $this->region()->id,
            ]);
        });

        $data = [
            'vpc_id' => $vpc->id,
        ];

        $this->patch('/v2/support/' . $this->vpcSupport->id, $data, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase(
            'vpc_support',
            $data,
            'ecloud'
        )
            ->assertResponseStatus(200);

    }
}
