<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\Network;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected Network $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->networkPolicy();
    }

    public function testGetCollection()
    {
        $this->asAdmin()
            ->get(
                '/v2/network-policies'
            )->assertJsonFragment([
                'id' => 'np-test',
                'network_id' => $this->network()->id,
                'name' => 'np-test',
            ])->assertStatus(200);
    }

    public function testGetResource()
    {
        $this->asAdmin()
            ->get(
                '/v2/network-policies/np-test'
            )->assertJsonFragment([
                'id' => 'np-test',
                'network_id' => $this->network()->id,
                'name' => 'np-test',
            ])->assertStatus(200);
    }

    public function testGetHiddenNotAdminFails()
    {
        $this->router()->setAttribute('is_management', true)->save();
        $this->asUser()
            ->get('/v2/network-policies/' . $this->networkPolicy()->id)
            ->assertStatus(404);
    }

    public function testGetHiddenAdminPasses()
    {
        $this->router()->setAttribute('is_management', true)->save();
        $this->asAdmin()
            ->get('/v2/network-policies/' . $this->networkPolicy()->id)
            ->assertStatus(200);
    }

    public function testAdminCanSeeLockedAttribute()
    {
        $this->asAdmin()
            ->get('/v2/network-policies/' . $this->networkPolicy()->id)
            ->assertJsonFragment([
                'locked' => false,
            ])->assertStatus(200);
    }

    public function testNonAdminCannotSeeLockedAttribute()
    {
        $this->asUser()
            ->get('/v2/network-policies/' . $this->networkPolicy()->id)
            ->assertJsonMissing([
                'locked' => false,
            ])->assertStatus(200);
    }
}