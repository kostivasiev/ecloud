<?php

namespace Tests\V2\Vpc;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testNoPermsIsDenied()
    {
        $this->delete('/v2/vpcs/' . $this->vpc()->id)->seeJson([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete('/v2/vpcs/x', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testNonMatchingResellerIdFails()
    {
        Event::fake();

        $this->vpc()->reseller_id = 3;
        $this->vpc()->save();
        $this->delete('/v2/vpcs/' . $this->vpc()->id, [], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testDeleteVpcWithResourcesFails()
    {
        $this->instance();
        $this->delete('/v2/vpcs/' . $this->vpc()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Precondition Failed',
            'detail' => 'The specified resource has dependant relationships and cannot be deleted',
            'status' => 412,
        ])->assertResponseStatus(412);
    }

    public function testSuccessfulDelete()
    {
        Event::fake(Created::class);

        $this->delete('/v2/vpcs/' . $this->vpc()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);
    }
}
