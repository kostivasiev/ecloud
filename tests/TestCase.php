<?php

namespace Tests;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Dhcp;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FloatingIp;
use App\Models\V2\HostGroup;
use App\Models\V2\HostSpec;
use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use App\Models\V2\ImageParameter;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\ArtisanService;
use App\Services\V2\ConjurerService;
use App\Services\V2\KingpinService;
use App\Services\V2\NsxService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Application;
use Tests\Traits\ResellerDatabaseMigrations;

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{
    use ResellerDatabaseMigrations,
        Mocks\Host\Mocks;

    /**
     * @deprecated use $this->be();
     * @var string[]
     */
    public $validReadHeaders = [
        'X-consumer-custom-id' => '1-1',
        'X-consumer-groups' => 'ecloud.read',
    ];

    /**
     * @deprecated use $this->be();
     * @var string[]
     */
    public $validWriteHeaders = [
        'X-consumer-custom-id' => '0-0',
        'X-consumer-groups' => 'ecloud.read, ecloud.write',
    ];

    /** @var Region */
    private $region;

    /** @var AvailabilityZone */
    private $availabilityZone;

    /** @var Vpc */
    private $vpc;

    /** @var Dhcp */
    private $dhcp;

    /** @var FirewallPolicy */
    private $firewallPolicy;

    /** @var NetworkPolicy */
    private $networkPolicy;

    /** @var RouterThroughput */
    private $routerThroughput;

    /** @var Router */
    private $router;

    /** @var NsxService */
    private $nsxServiceMock;

    /** @var KingpinService */
    private $kingpinServiceMock;

    /** @var ConjurerService */
    private $conjurerServiceMock;

    /** @var ArtisanService */
    private $artisanServiceMock;

    /** @var Credential */
    private $credential;

    /** @var Instance */
    private $instance;

    /** @var ApplianceVersion */
    private $applianceVersion;

    /** @var Appliance */
    private $appliance;

    /** @var Network */
    private $network;

    /** @var HostSpec */
    private $hostSpec;

    /** @var HostGroup */
    private $hostGroup;

    /** @var Image */
    private $image;

    /** @var ImageParameter */
    private $imageParameter;

    /** @var ImageMetadata */
    private $imageMetadata;

    /** @var Nic */
    private $nic;

    /** @var FloatingIp */
    private $floatingIp;

    /**
     * Creates the application.
     * @return Application
     */
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    public function firewallPolicy($id = 'fwp-test')
    {
        if (!$this->firewallPolicy) {
            Model::withoutEvents(function() use ($id) {
                $this->firewallPolicy = factory(FirewallPolicy::class)->create([
                    'id' => $id,
                    'router_id' => $this->router()->id,
                ]);
            });
        }
        return $this->firewallPolicy;
    }

    public function networkPolicy($id = 'np-test'): NetworkPolicy
    {
        if (!$this->networkPolicy) {
            Model::withoutEvents(function() use ($id) {
                $this->networkPolicy = factory(NetworkPolicy::class)->create([
                    'id' => $id,
                    'network_id' => $this->network()->id,
                ]);
            });
        }
        return $this->networkPolicy;
    }

    public function routerThroughput()
    {
        if (!$this->routerThroughput) {
            Model::withoutEvents(function() {
                $this->routerThroughput = factory(RouterThroughput::class)->create([
                    'id' => 'rtp-test',
                    'committed_bandwidth' => '1024',
                    'availability_zone_id' => $this->availabilityZone()->id
                ]);
            });
        }
        return $this->routerThroughput;
    }

    public function router()
    {
        if (!$this->router) {
            Model::withoutEvents(function() {
                $this->router = factory(Router::class)->create([
                    'id' => 'rtr-test',
                    'vpc_id' => $this->vpc()->id,
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'router_throughput_id' => $this->routerThroughput()->id,
                ]);
            });
        }
        return $this->router;
    }

    public function vpc()
    {
        if (!$this->vpc) {
            Model::withoutEvents(function () {
                $this->vpc = factory(Vpc::class)->create([
                    'id' => 'vpc-test',
                    'region_id' => $this->region()->id
                ]);
            });
        }
        return $this->vpc;
    }

    public function dhcp()
    {
        if (!$this->dhcp) {
            Model::withoutEvents(function () {
                $this->dhcp = factory(Dhcp::class)->create([
                    'id' => 'dhcp-test',
                    'vpc_id' => $this->vpc()->id,
                    'availability_zone_id' => $this->availabilityZone()->id,
                ]);
            });
        }
        return $this->dhcp;
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

    public function availabilityZone()
    {
        if (!$this->availabilityZone) {
            $this->availabilityZone = factory(AvailabilityZone::class)->create([
                'id' => 'az-test',
                'region_id' => $this->region()->id,
            ]);
        }
        return $this->availabilityZone;
    }

    public function instance()
    {
        if (!$this->instance) {
            Instance::withoutEvents(function() {
                $this->instance = factory(Instance::class)->create([
                    'id' => 'i-test',
                    'vpc_id' => $this->vpc()->id,
                    'name' => 'Test Instance ' . uniqid(),
                    'image_id' => $this->image()->id,
                    'vcpu_cores' => 1,
                    'ram_capacity' => 1024,
                    'platform' => 'Linux',
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'deploy_data' => [
                        'network_id' => $this->network()->id,
                        'volume_capacity' => 20,
                        'volume_iops' => 300,
                        'requires_floating_ip' => false,
                    ]
                ]);
            });

            //app()->bind(Volume::class);
        }
        return $this->instance;
    }

    public function nic()
    {
        if (!$this->nic) {
            Nic::withoutEvents(function() {
                $this->nic = factory(Nic::class)->create([
                    'id' => 'nic-test',
                    'mac_address' => 'AA:BB:CC:DD:EE:FF',
                    'instance_id' => $this->instance()->id,
                    'network_id' => $this->network()->id,
                ]);
            });
        }
        return $this->nic;
    }

    public function image()
    {
        if (!$this->image) {
            $this->image = factory(Image::class)->create([
                'id' => 'img-test',
                'vpc_id' => $this->vpc()->id,
            ]);
        }
        return $this->image;
    }

    public function imageParameter()
    {
        if (!$this->imageParameter) {
            $this->imageParameter = factory(ImageParameter::class)->make([
                'id' => 'iparam-test'
            ]);
            $this->image()->imageParameters()->save($this->imageParameter);
        }
        return $this->imageParameter;
    }

    public function imageMetadata()
    {
        if (!$this->imageMetadata) {
            $this->imageMetadata = factory(ImageMetadata::class)->make([
                'id' => 'imgmeta-test'
            ]);
            $this->image()->imageMetadata()->save($this->imageMetadata);
        }
        return $this->imageMetadata;
    }

    public function network()
    {
        if (!$this->network) {
            Model::withoutEvents(function() {
                $this->network = factory(Network::class)->create([
                    'id' => 'net-abcdef12',
                    'name' => 'Manchester Network',
                    'subnet' => '10.0.0.0/24',
                    'router_id' => $this->router()->id
                ]);
            });
        }
        return $this->network;
    }

    public function hostGroup()
    {
        if (!$this->hostGroup) {
            $this->hostGroup = Model::withoutEvents(function() {
                return factory(HostGroup::class)->create([
                    'id' => 'hg-test',
                    'name' => 'hg-test',
                    'vpc_id' => $this->vpc()->id,
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'host_spec_id' => $this->hostSpec()->id,
                    'windows_enabled' => true,
                ]);
            });
        }
        return $this->hostGroup;
    }

    public function floatingIp()
    {
        if (!$this->floatingIp) {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'vpc_id' => $this->vpc()->id,
            ]);
        }
        return $this->floatingIp;
    }

//    public function hostGroupJobMocks($id = 'hg-test')
//    {
//        // CreateCluster Job
//        $this->kingpinServiceMock()->expects('get')
//            ->with('/api/v2/vpc/vpc-test/hostgroup/' . $id)
//            ->andReturnUsing(function () {
//                return new Response(404);
//            });
//        $this->kingpinServiceMock()->expects('post')
//            ->withSomeOfArgs(
//                '/api/v2/vpc/vpc-test/hostgroup',
//                [
//                    'json' => [
//                        'hostGroupId' => $id,
//                        'shared' => false,
//                    ]
//                ]
//            )
//            ->andReturnUsing(function () {
//                return new Response(200);
//            });
//
//        // CreateTransportNode Job
//        $this->kingpinServiceMock()->expects('get')
//            ->with('/api/v2/vpc/vpc-test/network/switch')
//            ->andReturnUsing(function () {
//                return new Response(200, [], json_encode([
//                    'name' => 'test-network-switch-name',
//                    'uuid' => 'test-network-switch-uuid',
//                ]));
//            });
//        $this->nsxServiceMock()->expects('get')
//            ->with('/api/v1/transport-node-profiles')
//            ->andReturnUsing(function () {
//                return new Response(200, [], json_encode([
//                    'results' => [
//                        [
//                            'id' => 'TEST-TRANSPORT-NODE-PROFILE-ID',
//                            'display_name' => 'TEST-TRANSPORT-NODE-PROFILE-DISPLAY-NAME',
//                        ],
//                    ],
//                ]));
//            });
//        $this->nsxServiceMock()->expects('get')
//            ->with('/api/v1/search/query?query=resource_type:TransportZone%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-overlay-tz')
//            ->andReturnUsing(function () {
//                return new Response(200, [], json_encode([
//                    'results' => [
//                        [
//                            'id' => 'TEST-TRANSPORT-ZONE-ID',
//                            'transport_zone_profile_ids' => [
//                                'profile_id' => 'TEST-TRANSPORT-NODE-PROFILE-ID',
//                                'resource_type' => 'BfdHealthMonitoringProfile',
//                            ],
//                        ],
//                    ],
//                ]));
//            });
//        $this->nsxServiceMock()->expects('get')
//            ->with('/api/v1/search/query?query=resource_type:UplinkHostSwitchProfile%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-uplink-profile')
//            ->andReturnUsing(function () {
//                return new Response(200, [], json_encode([
//                    'results' => [
//                        [
//                            'id' => 'TEST-UPLINK-HOST-SWITCH-ID',
//                        ],
//                    ],
//                ]));
//            });
//        $this->nsxServiceMock()->expects('post')
//            ->withSomeOfArgs('/api/v1/transport-node-profiles')
//            ->andReturnUsing(function () {
//                return new Response(200);
//            });
//
//        // PrepareCluster Job
//        $this->nsxServiceMock()->expects('get')
//            ->with('/api/v1/transport-node-collections')
//            ->andReturnUsing(function () {
//                return new Response(200, [], json_encode([
//                    'results' => [
//                        [
//                            'id' => 'TEST-TRANSPORT-NODE-COLLECTION-ID',
//                            'display_name' => 'TEST-TRANSPORT-NODE-COLLECTION-DISPLAY-NAME',
//                        ],
//                    ],
//                ]));
//            });
//        $this->nsxServiceMock()->expects('get')
//            ->with('/api/v1/search/query?query=resource_type:TransportNodeProfile%20AND%20display_name:tnp-' . $id)
//            ->andReturnUsing(function () {
//                return new Response(200, [], json_encode([
//                    'results' => [
//                        [
//                            'id' => 'TEST-TRANSPORT-NODE-COLLECTION-ID',
//                        ],
//                    ],
//                ]));
//            });
//        $this->nsxServiceMock()->expects('get')
//            ->with('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=' . $id)
//            ->andReturnUsing(function () {
//                return new Response(200, [], json_encode([
//                    'results' => [
//                        [
//                            'external_id' => 'TEST-COMPUTE-COLLECTION-ID',
//                        ],
//                    ],
//                ]));
//            });
//        $this->nsxServiceMock()->expects('post')
//            ->withSomeOfArgs(
//                '/api/v1/transport-node-collections',
//                [
//                    'json' => [
//                        'resource_type' => 'TransportNodeCollection',
//                        'display_name' => 'tnc-' . $id,
//                        'description' => 'API created Transport Node Collection',
//                        'compute_collection_id' => 'TEST-COMPUTE-COLLECTION-ID',
//                        'transport_node_profile_id' => 'TEST-TRANSPORT-NODE-COLLECTION-ID',
//                    ]
//                ]
//            )
//            ->andReturnUsing(function () {
//                return new Response(200);
//            });
//    }

    public function hostGroupDestroyMocks()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-collections?compute_collection_id=TEST-COMPUTE-COLLECTION-ID')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => '92cba9bd-759c-465c-9e84-f6a1a19c4f11'
                        ]
                    ]
                ]));
            });
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-collections/92cba9bd-759c-465c-9e84-f6a1a19c4f11')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-profiles/92cba9bd-759c-465c-9e84-f6a1a19c4f11')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->kingpinServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup()->vpc->id . '/hostgroup/' . $this->hostGroup()->id)
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->nsxServiceMock()->expects('get')
            ->with('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=hg-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'external_id' => 'TEST-COMPUTE-COLLECTION-ID',
                        ],
                    ],
                ]));
            });
    }

    public function kingpinServiceMock()
    {
        if (!$this->kingpinServiceMock) {
            factory(Credential::class)->create([
                'id' => 'cred-kingpin',
                'name' => 'kingpinapi',
                'resource_id' => $this->availabilityZone()->id,
            ]);
            $this->kingpinServiceMock = \Mockery::mock(new KingpinService(new Client()))->makePartial();
            app()->bind(KingpinService::class, function () {
                return $this->kingpinServiceMock;
            });
        }
        return $this->kingpinServiceMock;
    }

    public function nsxServiceMock()
    {
        if (!$this->nsxServiceMock) {
            factory(Credential::class)->create([
                'id' => 'cred-nsx',
                'name' => 'NSX',
                'resource_id' => $this->availabilityZone()->id,
            ]);
            $nsxService = app()->makeWith(NsxService::class, [$this->availabilityZone()]);
            $this->nsxServiceMock = \Mockery::mock($nsxService)->makePartial();
            app()->bind(NsxService::class, function () {
                return $this->nsxServiceMock;
            });
        }
        return $this->nsxServiceMock;
    }

    public function hostSpec()
    {
        if (!$this->hostSpec) {
            $this->hostSpec = factory(HostSpec::class)->create([
                'id' => 'hs-test',
                'name' => 'test-host-spec',
            ]);
        }
        return $this->hostSpec;
    }

    public function artisanServiceMock()
    {
        if (!$this->artisanServiceMock) {
            factory(Credential::class)->create([
                'id' => 'cred-3par',
                'name' => '3PAR',
                'username' => config('artisan.user'),
                'resource_id' => $this->availabilityZone()->id,
            ]);

            factory(Credential::class)->create([
                'id' => 'cred-artisan',
                'name' => 'Artisan API',
                'username' => config('artisan.san_user'),
                'resource_id' => $this->availabilityZone()->id,
            ]);

            $this->artisanServiceMock = \Mockery::mock(new ArtisanService(new Client()))->makePartial();
            app()->bind(ArtisanService::class, function () {
                return $this->artisanServiceMock;
            });
        }
        return $this->artisanServiceMock;
    }

    public function conjurerServiceMock()
    {
        if (!$this->conjurerServiceMock) {
            factory(Credential::class)->create([
                'id' => 'cred-ucs',
                'name' => 'UCS API',
                'username' => config('conjurer.ucs_user'),
                'resource_id' => $this->availabilityZone()->id,
            ]);

            factory(Credential::class)->create([
                'id' => 'cred-conjurer',
                'name' => 'Conjurer API',
                'username' => config('conjurer.user'),
                'resource_id' => $this->availabilityZone()->id,
            ]);

            $this->conjurerServiceMock = \Mockery::mock(new ConjurerService(new Client()))->makePartial();
            app()->bind(ConjurerService::class, function () {
                return $this->conjurerServiceMock;
            });
        }
        return $this->conjurerServiceMock;
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

        // Using these mocks means we have to use DatabaseMigration by default, but the ability to catch 3rd party
        // API calls being performed in tests is more than worth the extra overhead.
        $this->kingpinServiceMock();
        $this->nsxServiceMock();

        Event::fake([
            // V1 hack
            \App\Events\V1\DatastoreCreatedEvent::class,

            // Creating
            \App\Events\V2\Instance\Creating::class,

            // Created
            \App\Events\V2\AvailabilityZone\Created::class,
            \App\Events\V2\Nat\Created::class,

            // Deleting
            \App\Events\V2\Nat\Deleting::class,

            // Deleted
            \App\Events\V2\AvailabilityZone\Deleted::class,
            \App\Events\V2\Nat\Deleted::class,
            \App\Events\V2\FloatingIp\Deleted::class,
            \App\Events\V2\Network\Deleted::class,
            \App\Events\V2\Router\Deleted::class,

            // Saved
            \App\Events\V2\Nat\Saved::class,
            \App\Events\V2\AvailabilityZoneCapacity\Saved::class,

            // Saving
            \App\Events\V2\Nat\Saving::class,
        ]);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
