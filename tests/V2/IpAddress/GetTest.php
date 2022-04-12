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
            ->create([
                'network_id' => $this->network()->id
            ]);

        $this->get('/v2/ip-addresses?sort=ip_address:asc')
            ->assertJsonFragment(
                [
                    'ip_address' => '1.1.1.1',
                    'network_id' => $this->network()->id,
                    'type' => 'normal'
                ]
            )->assertStatus(200);
    }

    public function testGetResource()
    {
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id
        ]);

        $this->get('/v2/ip-addresses/' . $ipAddress->id)
            ->assertJsonFragment(
                [
                    'id' => $ipAddress->id,
                    'ip_address' => '1.1.1.1',
                    'network_id' => $this->network()->id,
                    'type' => 'normal'
                ]
            )->assertStatus(200);
    }

    public function testGetNicsCollection()
    {
        $ipAddress = IpAddress::factory()->create();

        $ipAddress->nics()->sync($this->nic());

        $this->get('/v2/ip-addresses/' . $ipAddress->id . '/nics')
            ->assertJsonFragment(
                [
                    'id' => $this->nic()->id,
                ]
            )->assertStatus(200);
    }
}