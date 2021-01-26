<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\NsxService;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected $firewallPolicy;
    protected $region;
    protected $router;
    protected $vpc;
    protected $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');
            $mockEncryptionServiceProvider->shouldReceive('decrypt')->andReturn('somepassword');
            return $mockEncryptionServiceProvider;
        });

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        factory(Credential::class)->create([
            'name' => 'NSX',
            'resource_id' => $this->availabilityZone->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->id
        ]);

        $nsxService = app()->makeWith(NsxService::class, [$this->availabilityZone]);
        $mockNsxService = \Mockery::mock($nsxService)->makePartial();
        app()->bind(NsxService::class, function () use ($mockNsxService) {
            $mockNsxService->shouldReceive('patch')
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
            $mockNsxService->shouldReceive('get')
                ->withArgs(['policy/api/v1/infra/domains/default/gateway-policies/?include_mark_for_delete_objects=true'])
                ->andReturn(new Response(200, [], json_encode(['results' => [['id' => 0]]])));
            $mockNsxService->shouldReceive('get')
                ->withArgs(['/policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/fwp-test'])
                ->andReturn(new Response(200, [], json_encode(['results' => [['id' => 0]]])));
            $mockNsxService->shouldReceive('delete')
                ->andReturn(new Response(204, [], ''));
            return $mockNsxService;
        });

        $this->firewallPolicy = factory(FirewallPolicy::class)->create([
            'id' => 'fwp-test',
            'router_id' => $this->router->id,
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete('/v2/firewall-policies/' . $this->firewallPolicy->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->firewallPolicy->refresh();
        $this->assertNotNull($this->firewallPolicy->deleted_at);
    }
}
