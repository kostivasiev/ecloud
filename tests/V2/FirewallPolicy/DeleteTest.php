<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\FirewallPolicy;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->nsxServiceMock()->allows('patch')
            ->withArgs([
                '/policy/api/v1/infra/domains/default/gateway-policies/fwp-test',
                [
                    'json' => [
                        'id' => 'fwp-test',
                        'display_name' => 'fwp-test',
                        'description' => 'name',
                        'sequence_number' => 10,
                        'rules' => [],
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->allows('get')
            ->withArgs(['/policy/api/v1/infra/domains/default/gateway-policies/?include_mark_for_delete_objects=true'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['results' => [['id' => 0]]]));
            });
        $this->nsxServiceMock()->allows('get')
            ->withArgs(['/policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/fwp-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });
        $this->nsxServiceMock()->allows('delete')
            ->withArgs(['/policy/api/v1/infra/domains/default/gateway-policies/fwp-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
    }

    public function testSuccessfulDelete()
    {
        $this->asAdmin()
            ->delete('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertStatus(202);
        $this->firewallPolicy()->refresh();
        $this->assertNotNull($this->firewallPolicy()->deleted_at);
    }

    public function testUserCannotDeleteLockedPolicy()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();
        $this->asUser()
            ->delete('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The specified resource is locked',
            ])->assertStatus(403);
        $this->firewallPolicy()->refresh();
        $this->assertNull($this->firewallPolicy()->deleted_at);
    }

    public function testAdminCanDeleteLockedPolicy()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();
        $this->asAdmin()
            ->delete('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertStatus(202);
        $this->firewallPolicy()->refresh();
        $this->assertNotNull($this->firewallPolicy()->deleted_at);
    }
}
