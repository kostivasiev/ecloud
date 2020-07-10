<?php

namespace Tests\V2\VirtualDataCentres;

use App\Models\V2\VirtualDataCentres;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testNoPermsIsDenied()
    {
        $vdc = $this->createDataCentre();
        $data = [
            'name'    => 'Manchester DC',
        ];
        $this->patch(
            '/v2/vdcs/' . $vdc->getKey(),
            $data,
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNullNameIsDenied()
    {
        $vdc = $this->createDataCentre();
        $data = [
            'name'    => '',
        ];
        $this->patch(
            '/v2/vdcs/' . $vdc->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The name field, when specified, cannot be null',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $vdc = $this->createDataCentre();
        $data = [
            'name'    => 'Manchester DC',
        ];
        $this->patch(
            '/v2/vdcs/' . $vdc->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $virtualDataCentre = VirtualDataCentres::findOrFail($vdc->getKey());
        $this->assertEquals($data['name'], $virtualDataCentre->name);
    }

    /**
     * Create Data Centre
     * @return \App\Models\V2\VirtualDataCentres
     */
    public function createDataCentre(): VirtualDataCentres
    {
        $vdc = factory(VirtualDataCentres::class, 1)->create()->first();
        $vdc->save();
        $vdc->refresh();
        return $vdc;
    }

}