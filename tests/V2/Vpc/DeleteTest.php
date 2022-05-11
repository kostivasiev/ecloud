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
        $this->delete('/v2/vpcs/' . $this->vpc()->id)
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])->assertStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete('/v2/vpcs/x', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertStatus(404);
    }

    public function testNonMatchingResellerIdFails()
    {
        Event::fake();

        $this->vpc()->reseller_id = 3;
        $this->vpc()->save();
        $this->delete('/v2/vpcs/' . $this->vpc()->id, [], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertStatus(404);
    }

    public function testDeleteVpcWithResourcesFails()
    {
        $this->instanceModel();
        $this->delete('/v2/vpcs/' . $this->vpc()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment([
            'title' => 'Precondition Failed',
            'detail' => 'The specified resource has dependant relationships and cannot be deleted',
            'status' => 412,
        ])->assertStatus(412);
    }

    public function testDeleteVpcWithManagementResourceDoesNotFail()
    {
        Event::fake(Created::class);
        $this->router()->setAttribute('is_management', true)->saveQuietly();
        $this->delete('/v2/vpcs/' . $this->vpc()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);
    }

    public function testSuccessfulDelete()
    {
        Event::fake(Created::class);

        $this->delete('/v2/vpcs/' . $this->vpc()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);
    }

    public function testDeletionFailsIfVpcHasHostGroup()
    {
        $this->hostGroup();
        $this->delete('/v2/vpcs/' . $this->vpc()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment(
            [
                'title' => 'Precondition Failed',
                'detail' => 'The specified resource has dependant relationships and cannot be deleted',
                'status' => 412,
            ]
        )->assertStatus(412);
    }
}
