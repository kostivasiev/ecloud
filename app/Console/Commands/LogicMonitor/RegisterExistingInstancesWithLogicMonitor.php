<?php

namespace App\Console\Commands\LogicMonitor;

use App\Jobs\Router\CreateCollectorRules;
use App\Jobs\Router\CreateSystemPolicy;
use App\Models\V2\Credential;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use App\Models\V2\NetworkRule;
use App\Models\V2\Task;
use App\Services\V2\PasswordService;
use App\Support\Sync;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Log;
use UKFast\Admin\Account\AdminClient as AccountAdminClient;
use UKFast\Admin\Monitoring\AdminClient as MonitoringAdminClient;
use UKFast\Admin\Monitoring\Entities\Account;
use UKFast\Admin\Monitoring\Entities\Device;
use UKFast\SDK\Exception\ApiException;
use UKFast\SDK\Exception\NotFoundException;

class RegisterExistingInstancesWithLogicMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lm:register-all-instances {--T|test-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(
        public int $updated = 0,
        public int $failed = 0,
        public int $skipped = 0
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        MonitoringAdminClient $adminMonitoringClient,
        AccountAdminClient $accountAdminClient
    ) {
        $networks = Network::withoutTrashed()->with('router')->with('router.vpc')->get();

        foreach ($networks as $network) {
            $router = $network->router;
            $vpc = $network->router->vpc;

            $this->createSystemPolicy($router);

            // check for collector in routers AZ, if none then skip
            // create LM rule to open ports inbound from collectors IP
            $this->createFirewallRules($router);
            foreach ($network->networkPolicy()->get() as $policy) {
                if ($vpc->advanced_networking) {
                    $this->createNetworkRules($policy);
                }
            }
        }

        $this->line('------------------------------------');

        $instances = Instance::withoutTrashed()->with('availabilityZone')->with('vpc')->get();

        foreach ($instances as $instance) {
            // Check if fIP assigned, if not then skip
            $floatingIp = FloatingIp::where(function ($query) use ($instance) {
                $query->whereIn('resource_id', $instance->nics()->pluck('id'));
                $query->orWhereIn('resource_id', IpAddress::whereHas('nics', function ($query) use ($instance) {
                    $query->whereIn('id', $instance->nics()->pluck('id'));
                    $query->where('type', IpAddress::TYPE_DHCP);
                })->pluck('id'));
            })->first();

            if (empty($floatingIp)) {
                $this->info('No floating IP assigned to instance ' . $instance->id . ', skipping');
                $this->skipped++;
                continue;
            }

            // Check if device is registered, if so then skip
            $device = $adminMonitoringClient->devices()->getAll([
                'reference_type' => 'server',
                'reference_id:eq' => $instance->id
            ]);
            if (!empty($device)) {
                $this->info('Device ' . $instance->id . ' is already registered, skipping');
                $this->skipped++;
                continue;
            }

            // check if LM creds exist, if not create
            $availabilityZone = $instance->availabilityZone;

            $collectorsPage = $adminMonitoringClient->collectors()->getPage(1, 15, [
                'datacentre_id' => $availabilityZone->datacentre_site_id,
                'is_shared' => true
            ]);

            if ($collectorsPage->totalItems() < 1) {
                $this->error('Failed to load logic monitor collector for availability zone ' . $availabilityZone->id);
                $this->failed++;
                continue;
            }

            $collector = $collectorsPage->getItems()[0];

            $logicMonitorCredentials = $instance->credentials()
                ->where('username', 'lm.' . $instance->id)
                ->first();
            if (empty($logicMonitorCredentials)) {
                $this->info('No logic monitor credentials found for instance ' . $instance->id);

                $logicMonitorCredentials = $this->createLMCredentials($instance);
                if ($logicMonitorCredentials === false) {
                    $this->failed++;
                    continue;
                }
            }

            // check account exists for customer, if not then create
            $accounts = $adminMonitoringClient->setResellerId($instance->vpc->reseller_id)->accounts()->getAll();
            if (!empty($accounts)) {
                $this->saveLogicMonitorAccountId($accounts[0]->id, $instance);
                $this->info('Logic Monitor account already exists, skipping');
            } else {
                try {
                    $customer = $accountAdminClient->customers()->getById($instance->vpc->reseller_id);
                } catch (NotFoundException) {
                    $this->error('Failed to load account details for reseller_id ' . $instance->vpc->reseller_id);
                    continue;
                }

                if (!$this->option('test-run')) {
                    $response = $adminMonitoringClient->accounts()->createEntity(new Account([
                        'name' => $customer->name
                    ]));

                    $id = $response->getId();
                } else {
                    $id = 'test-test-test-test';
                }

                $this->saveLogicMonitorAccountId($id, $instance);
                $this->info('Logic Monitor account ' . $id . ' created for instance : ' . $instance->id);
            }

            if (!$this->option('test-run')) {
                // register device
                try {
                    $response = $adminMonitoringClient->devices()->createEntity(new Device([
                        'reference_type' => 'server',
                        'reference_id' => $instance->id,
                        'collector_id' => $collector->id,
                        'display_name' => $instance->id,
                        'account_id' => $instance->deploy_data['logic_monitor_account_id'],
                        'ip_address' => $floatingIp->getIPAddress(),
                        'snmp_community' => 'public',
                        'platform' => $instance->platform,
                        'username' => $logicMonitorCredentials->username,
                        'password' => $logicMonitorCredentials->password,
                    ]));

                    $deviceId = $response->getId();
                    $deploy_data = $instance->deploy_data;
                    $deploy_data['logic_monitor_device_id'] = $deviceId;
                    $instance->deploy_data = $deploy_data;
                    $instance->save();
                } catch (ApiException $exception) {
                    $this->error('Failed register instance ' . $instance->id . ' device on monitoring API:  ' . print_r($exception->getErrors(), true));
                    continue;
                }
            } else {
                $deviceId = 'test-test-test-test';
            }

            $this->info('Logic Monitor device registered for instance ' . $instance->id . ' : ' . $deviceId);
            $this->updated++;

            $this->line('------------------------------------');
        }

        $this->line('------------------------------------');

        $this->info(
            'Total Updated: ' . $this->updated .
            ', Total Skipped: ' . $this->skipped .
            ', Total Failures: ' . $this->failed
        );

        return 0;
    }

    /**
     * @param $id
     * @param $instance
     * @return bool
     */
    protected function saveLogicMonitorAccountId($id, $instance): bool
    {
        $deploy_data = $instance->deploy_data;
        $deploy_data['logic_monitor_account_id'] = $id;
        $instance->deploy_data = $deploy_data;
        $this->info('Logic monitor account ID ' . $id . ' stored for instance ' . $instance->id);
        if ($this->option('test-run')) {
            return true;
        }
        return $instance->save();
    }

    /**
     * @param $router
     */
    private function createSystemPolicy($router): void
    {
        $task = new Task([
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $task->resource()->associate($router);
        $task->save();

        $this->info('Creating System policy for router ' . $router->id);
        if (!$this->option('test-run')) {
            dispatch(new CreateSystemPolicy($task));
        }
    }

    private function createNetworkRules($networkPolicy): void
    {
        $rules = config('defaults.network_policy.rules');

        foreach ($rules as $rule) {
            if ($networkPolicy->networkRules()->where('name', $rule['name'])->count() < 1) {
                $this->info('Creating network rule ' . $rule['name'] . ' for network policy ' . $networkPolicy->id);
                $networkRule = new NetworkRule($rule);
                if (!$this->option('test-run')) {
                    $networkPolicy->networkRules()->save($networkRule);
                }
            }
        }
    }

    private function createFirewallRules($router): void
    {
        $task = new Task([
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $task->resource()->associate($router);
        $task->save();
        $this->info('Creating logic monitor firewall rules for router ' . $router->id);
        if (!$this->option('test-run')) {
            dispatch(new CreateCollectorRules($task));
        }
    }

    /**
     * @param $instance
     * @return Credential|false|mixed
     * @throws BindingResolutionException
     */
    private function createLMCredentials($instance)
    {
        $guestAdminCredential = $instance->getGuestAdminCredentials();

        if (!$guestAdminCredential) {
            $this->error('Failed to create logic monitor credentials: No admin credentials found for instance ' . $instance->id);
            return false;
        }

        list($username, $password, $hidden, $sudo) =
            ['lm.' . $instance->id, resolve(PasswordService::class)->generate(24), true, false];
        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => $username,
            'resource_id' => $instance->id,
            'username' => $username,
            'is_hidden' => $hidden,
            'port' => '2020',
        ]);
        $credential->password = $password;

        $this->info('Creating logic monitor credentials for instance ' . $instance->id);
        if (!$this->option('test-run')) {
            $credential->save();
        }

        if (!$this->option('test-run')) {
            try {
                $instance->availabilityZone->kingpinService()->post(
                    '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/linux/user',
                    [
                        'json' => [
                            'targetUsername' => $username,
                            'targetPassword' => $credential->password,
                            'targetSudo' => $sudo,
                            'username' => $guestAdminCredential->username,
                            'password' => $guestAdminCredential->password,
                        ],
                    ]
                );
            } catch (\Exception $exception) {
                $message = ($exception instanceof RequestException && $exception->hasResponse()) ?
                    $exception->getResponse()->getBody()->getContents() :
                    $exception->getMessage();
                $this->error('Failed to create logic monitor credentials on instance: ' . $message);
                return false;
            }
        }

        return $credential;
    }
}
