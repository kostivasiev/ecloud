<?php

namespace Tests\unit\Listeners\FirewallPolicy;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\NsxService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $region;
    protected $availability_zone;
    protected $vpc;
    protected $router;
    protected $firewallPolicy;
    protected $firewallRule;

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

        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        factory(Credential::class)->create([
            'name' => 'NSX',
            'resource_id' => $this->availability_zone->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = factory(Router::class)->create([
            'availability_zone_id' => $this->availability_zone->id,
        ]);
        $this->firewallPolicy = factory(FirewallPolicy::class)->create([
            'router_id' => $this->router->id
        ]);
        $this->firewallRule = factory(FirewallRule::class)->create([
            'firewall_policy_id' => $this->firewallPolicy->id
        ]);
        $this->firewallRulePort = factory(FirewallRulePort::class)->create([
            'firewall_rule_id' => $this->firewallRule->id,
        ]);

        $nsxService = app()->makeWith(NsxService::class, [$this->availability_zone]);
        $mockNsxService = \Mockery::mock($nsxService)->makePartial();
        app()->bind(NsxService::class, function () use ($mockNsxService) {
            $mockNsxService->shouldReceive('get')->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->router->id . '/state'
            ])->andReturn(
                new Response(200, [], json_encode(['tier1_state' => ['state' => 'in_sync']])),
                new Response(200, [], json_encode(['tier1_state' => ['state' => 'in_sync']]))
            );
            return $mockNsxService;
        });
    }

    public function testDeletingUndeploys()
    {
        $this->assertNull($this->firewallRule->refresh()->deleted_at);
        $this->firewallPolicy->delete();
        $this->assertNotNull($this->firewallRule->refresh()->deleted_at);
    }
}
