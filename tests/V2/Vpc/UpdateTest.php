<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    public function testNoPermsIsDenied()
    {
        $this->patch('/v2/vpcs/' . $this->vpc()->id, [
            'name' => 'Manchester DC',
        ])->seeJson([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testNullNameIsDenied()
    {
        $this->patch('/v2/vpcs/' . $this->vpc()->id, [
            'name' => '',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The name field, when specified, cannot be null',
            'status' => 422,
            'source' => 'name'
        ])->assertResponseStatus(422);
    }

    public function testNonMatchingResellerIdFails()
    {
        $this->vpc()->reseller_id = 3;
        $this->vpc()->save();
        $this->patch('/v2/vpcs/' . $this->vpc()->id, [
            'name' => 'Manchester DC',
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testValidDataIsSuccessful()
    {
        $data = [
            'name' => 'name',
            'reseller_id' => 2,
        ];
        $this->patch('/v2/vpcs/' . $this->vpc()->id, $data, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase('vpcs', $data, 'ecloud')
            ->assertResponseStatus(200);
        $this->assertEquals($data['name'], Vpc::findOrFail($this->vpc()->id)->name);
    }
}
