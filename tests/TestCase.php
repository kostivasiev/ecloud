<?php

namespace Tests;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\NsxService;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Application;

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{
    public $validReadHeaders = [
        'X-consumer-custom-id' => '1-1',
        'X-consumer-groups' => 'ecloud.read',
    ];
    public $validWriteHeaders = [
        'X-consumer-custom-id' => '0-0',
        'X-consumer-groups' => 'ecloud.write',
    ];

    /** @var Region */
    private $region;

    /** @var AvailabilityZone */
    private $availabilityZone;

    /** @var Vpc */
    private $vpc;

    /** @var FirewallPolicy */
    private $firewallPolicy;

    /** @var Router */
    private $router;

    /** @var NsxService */
    private $nsxServiceMock;

    /** @var Credential */
    private $credential;

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    public function firewallPolicy($id = 'fwp-test')
    {
        if (!$this->firewallPolicy) {
            $this->firewallPolicy = factory(FirewallPolicy::class)->create([
                'id' => $id,
                'router_id' => $this->router()->id,
            ]);
        }
        return $this->firewallPolicy;
    }

    public function router()
    {
        if (!$this->router) {
            $this->router = factory(Router::class)->create([
                'id' => 'rtr-test',
                'vpc_id' => $this->vpc()->id
            ]);
        }
        return $this->router;
    }

    public function vpc()
    {
        if (!$this->vpc) {
            $this->vpc = factory(Vpc::class)->create([
                'id' => 'vpc-test',
                'region_id' => $this->region()->id
            ]);
        }
        return $this->vpc;
    }

    public function region()
    {
        if (!$this->region) {
            $this->region = factory(Region::class)->create([
                'id' => 'reg-test',
            ]);
        }
        return $this->region;
    }

    public function nsxServiceMock()
    {
        if (!$this->nsxServiceMock) {
            $nsxService = app()->makeWith(NsxService::class, [$this->availabilityZone()]);
            $this->nsxServiceMock = \Mockery::mock($nsxService)->makePartial();
            app()->bind(NsxService::class, function () {
                return $this->nsxServiceMock;
            });
        }
        return $this->nsxServiceMock;
    }

    public function availabilityZone()
    {
        if (!$this->availabilityZone) {
            $this->availabilityZone = factory(AvailabilityZone::class)->create([
                'id' => 'az-test',
                'region_id' => $this->region()->id,
            ]);
            $this->credential();
        }
        return $this->availabilityZone;
    }

    public function credential()
    {
        if (!$this->credential) {
            $this->credential = factory(Credential::class)->create([
                'id' => 'cred-test',
                'name' => 'NSX',
                'resource_id' => $this->availabilityZone->id,
            ]);
        }
        return $this->credential;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');
            $mockEncryptionServiceProvider->shouldReceive('decrypt')->andReturn('somepassword');
            return $mockEncryptionServiceProvider;
        });

        Event::fake([
            // V1 hack
            \App\Events\V1\DatastoreCreatedEvent::class,

            // Creating
            \App\Events\V2\Instance\Creating::class,

            // Created
            \App\Events\V2\AvailabilityZone\Created::class,
            \App\Events\V2\Dhcp\Created::class,
            \App\Events\V2\Instance\Created::class,
            \App\Events\V2\Network\Created::class,
            \App\Events\V2\Router\Created::class,
            \App\Events\V2\Vpc\Created::class,
            \App\Events\V2\FloatingIp\Created::class,
            \App\Events\V2\Nat\Created::class,
            \App\Events\V2\Nic\Created::class,

            // Deleting
            \App\Events\V2\Nat\Deleting::class,

            // Deleted
            \App\Events\V2\AvailabilityZone\Deleted::class,
            \App\Events\V2\Nat\Deleted::class,
            \App\Events\V2\FirewallRule\Deleted::class,
            \App\Events\V2\Vpc\Deleted::class,
            \App\Events\V2\Dhcp\Deleted::class,
            \App\Events\V2\Nic\Deleted::class,
            \App\Events\V2\FirewallRulePort\Deleted::class,
            \App\Events\V2\FloatingIp\Deleted::class,
            \App\Events\V2\Volume\Deleted::class,
            \App\Events\V2\Network\Deleted::class,
            \App\Events\V2\Router\Deleted::class,

            // Saved
            \App\Events\V2\Router\Saved::class,
            \App\Events\V2\Network\Saved::class,
            \App\Events\V2\Nat\Saved::class,
            \App\Events\V2\AvailabilityZoneCapacity\Saved::class,
            \App\Events\V2\Volume\Saved::class,

            // Updated
            \App\Events\V2\Sync\Updated::class,
            \App\Events\V2\Instance\Updated::class,

            // Saving
            \App\Events\V2\Router\Saving::class,
            \App\Events\V2\Network\Saving::class,
            \App\Events\V2\Nic\Saving::class,
            \App\Events\V2\Nat\Saving::class,

            // Deleting
            \App\Events\V2\Volume\Saving::class,
            \App\Events\V2\Instance\Saving::class,

            // Deploy
            \App\Events\V2\Instance\Deploy::class,
        ]);
    }
}
