<?php
namespace Tests\V2\IpAddress;

use App\Models\V2\IpAddress;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testGetCollection()
    {
        IpAddress::factory()
            ->count(2)
            ->state(new Sequence(
                ['ip_address' => '1.1.1.1'],
                ['ip_address' => '2.2.2.2'],
            ))
            ->create();

        $this->get('/v2/ip-addresses')
            ->seeJson(
                [
                    'ip_address' => '1.1.1.1',
                    'type' => 'normal'
                ]
            )->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $ipAddress = IpAddress::factory()->create();

        $this->get('/v2/ip-addresses/' . $ipAddress->id)
            ->seeJson(
                [
                    'id' => $ipAddress->id,
                    'ip_address' => '1.1.1.1',
                    'type' => 'normal'
                ]
            )->assertResponseStatus(200);
    }

    public function testGetNicsCollection()
    {
        $ipAddress = IpAddress::factory()->create();

        $ipAddress->nics()->sync($this->nic());

        $this->get('/v2/ip-addresses/' . $ipAddress->id . '/nics')
            ->seeJson(
                [
                    'id' => $this->nic()->id,
                ]
            )->assertResponseStatus(200);
    }
}