<?php

namespace Tests\V2\FirewallPolicy;

use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                'policy/api/v1/infra/domains/default/gateway-policies/fwp-test',
                [
                    'json' => [
                        'id' => 'fwp-test',
                        'display_name' => 'name',
                        'description' => 'name',
                        'sequence_number' => 10,
                        'rules' => [],
                    ]
                ]
            ])
            ->andReturn(new Response(200, [], ''));
        $this->nsxServiceMock()->shouldReceive('get')
            ->withArgs(['policy/api/v1/infra/domains/default/gateway-policies/?include_mark_for_delete_objects=true'])
            ->andReturn(new Response(200, [], json_encode(['results' => [['id' => 0]]])));
        $this->nsxServiceMock()->shouldReceive('get')
            ->withArgs(['/policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/fwp-test'])
            ->andReturn(new Response(200, [], json_encode(['results' => [['id' => 0]]])));
        $this->nsxServiceMock()->shouldReceive('delete')
            ->withArgs(['policy/api/v1/infra/domains/default/gateway-policies/fwp-test'])
            ->andReturn(new Response(204, [], ''));
    }

    public function testSuccessfulDelete()
    {
        $this->delete('/v2/firewall-policies/' . $this->firewallPolicy()->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->firewallPolicy()->refresh();
        $this->assertNotNull($this->firewallPolicy()->deleted_at);
    }
}
