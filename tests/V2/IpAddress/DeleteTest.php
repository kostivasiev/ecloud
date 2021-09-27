<?php
namespace Tests\V2\IpAddress;

use App\Models\V2\IpAddress;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    public function testSuccessfulDelete()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $ipAddress = IpAddress::factory()->create();

        $this->delete('/v2/ip-addresses/' . $ipAddress->id)
            ->assertResponseStatus(204);
    }

    public function testDeleteAssignedToResourceFails()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $ipAddress = IpAddress::factory()->create();

        $ipAddress->nics()->sync($this->nic());

        $this->delete('/v2/ip-addresses/' . $ipAddress->id)
            ->assertResponseStatus(403);
    }
}