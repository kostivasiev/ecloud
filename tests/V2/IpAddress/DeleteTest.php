<?php
namespace Tests\V2\IpAddress;

use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    use VipMock;

    public function testSuccessfulDelete()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->delete('/v2/ip-addresses/' . $this->ip()->id)
            ->assertStatus(204);
    }

    public function testCannotDeleteWhenUsedByNic()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write'])));
        $this->ip()->nics()->sync($this->nic());

        $this->delete('/v2/ip-addresses/' . $this->ip()->id)
            ->assertStatus(412);
    }

    public function testCannotDeleteWhenUsedByVip()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write'])));
        $this->vip()->setAttribute('ip_address_id', $this->ip()->id)->saveQuietly();

        $this->delete('/v2/ip-addresses/' . $this->ip()->id)
            ->assertStatus(412);
    }

    public function testCannotDeleteWhenUsedByVipAndNic()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write'])));
        $this->ip()->nics()->sync($this->nic());
        $this->vip()->setAttribute('ip_address_id', $this->ip()->id)->saveQuietly();

        $this->delete('/v2/ip-addresses/' . $this->ip()->id)
            ->assertStatus(412);
    }
}