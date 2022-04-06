<?php

namespace App\Console\Commands\LogicMonitor;

use App\Jobs\NetworkPolicy\AllowLogicMonitor;
use App\Jobs\Router\CreateCollectorRules;
use App\Jobs\Router\CreateSystemPolicy;
use App\Models\V2\Credential;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Services\V2\PasswordService;
use App\Support\Sync;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Log;
use UKFast\Admin\ManagedCloudflare\V1\AccountAdminClient;
use UKFast\Admin\Monitoring\AdminClient as MonitoringAdminClient;
use UKFast\Admin\Monitoring\Entities\Account;
use UKFast\Admin\Monitoring\Entities\Device;
use UKFast\SDK\Exception\NotFoundException;

class RegisterExistingInstancesWithLogicMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lm:register-all-instances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $passwordService;
    
    private Task $task;

    public function __construct(PasswordService $passwordService)
    {
        parent::__construct();
        $this->passwordService = $passwordService;
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
        $routers = Router::withoutTrashed()->get();

        foreach ($routers as $router) {
            $network = $router->network();

            // check 'system' policy exists, if not then create
            /** @var FirewallPolicy $firewallPolicy */
            $systemPolicy = $router->whereHas('firewallPolicies', function ($query) {
                $query->where('name', '=', 'System');
            })->first();

            if (!$systemPolicy) {
                try {
                    $this->createSystemPolicy($router);
                } catch (\Exception $exception) {
                    //error creating system policy
                    break;
                }
            }

            // check for collector in routers AZ, if none then skip
            // create LM rule to open ports inbound from collectors IP
            $this->createFirewallRules($router);
            foreach ($network->policies() as $policy) {
                $this->createNetworkRules($policy);
            }
        }

        $instances = Instance::withoutTrashed()->get();

        foreach ($instances as $instance) {
            // check if device is registered, if so then skip
            $device = $adminMonitoringClient->devices()->getAll([
                'reference_type' => 'server',
                'reference_id:eq' => $instance->id
            ]);

            if (!empty($device)) {
                Log::info($this::class . ' : The device is already registered, skipping');
                break;
            }

            // check if LM creds exist, if not create
            $availabilityZone = $instance->availabilityZone;

            $collectorsPage = $adminMonitoringClient->collectors()->getPage(1, 15, [
                'datacentre_id' => $availabilityZone->datacentre_site_id,
                'is_shared' => true
            ]);

            if ($collectorsPage->totalItems() < 1) {
                Log::warning('Failed to load logic monitor collector for availability zone ' . $availabilityZone->id);
                break;
            }

            $collector = $collectorsPage->getItems()[0];

            $logicMonitorCredentials = $instance->credentials()
                ->where('username', 'lm.' . $instance->id)
                ->first();
            if (!$logicMonitorCredentials) {
                if (!($logicMonitorCredentials = $this->createLMCredentials($instance))) {#
                    //cant get guest admin credentials
                    break;
                }
            }

            // check account exists for customer, if not then create
            $accounts = $adminMonitoringClient->setResellerId($instance->vpc->reseller_id)->accounts()->getAll();
            if (!empty($accounts)) {
                $this->saveLogicMonitorAccountId($accounts[0]->id, $instance);
                Log::info($this::class . ' : Logic Monitor account already exists, skipping', [
                    'logic_monitor_account_id' => $accounts[0]->id
                ]);
                break;
            }

            try {
                $customer = $accountAdminClient->customers()->getById($instance->vpc->reseller_id);
            } catch (NotFoundException) {
                Log::error(new \Exception('Failed to load account details for reseller_id ' . $instance->vpc->reseller_id));
                break;
            }

            $response = $adminMonitoringClient->accounts()->createEntity(new Account([
                'name' => $customer->name
            ]));

            $id = $response->getId();
            $this->saveLogicMonitorAccountId($id, $instance);
            Log::info($this::class . ' : Logic Monitor account created: ' . $id);

            // check if fIP assigned, if not then skip
            if (empty($instance->deploy_data['floating_ip_id'])) {
                Log::info(get_class($this) . ' : No floating IP assigned to the instance, skipping');
                break;
            }
            $floatingIp = FloatingIp::find($instance->deploy_data['floating_ip_id']);
            if (!$floatingIp) {
                Log::error(new \Exception('Failed to load floating IP for instance', [
                    'instance_id' => $instance->id,
                    'floating_ip_id' => $instance->deploy_data['floating_ip_id']
                ]));
                break;
            }

            // register device
            $response = $adminMonitoringClient->devices()->createEntity(new Device([
                'reference_type' => 'server',
                'reference_id' => $instance->id,
                'collector_id' => $collector->id,
                'display_name' => $instance->name,
                'tier_id' => '8485a243-8a83-11ec-915e-005056ad1662', // This is the free tier from Monitoring APIO
                'account_id' => $instance->deploy_data['logic_monitor_account_id'],
                'ip_address' => $floatingIp->getIPAddress(),
                'snmp_community' => 'public',
                'platform' => $instance->platform,
                'username' => $logicMonitorCredentials->username,
                'password' => $logicMonitorCredentials->password,
            ]));

            $deploy_data = $instance->deploy_data;
            $deploy_data['logic_monitor_device_id'] = $response->getId();
            $instance->deploy_data = $deploy_data;
            $instance->save();

            Log::info($this::class . ' : Logic Monitor device registered : ' . $deploy_data['logic_monitor_device_id'], [
                'instance_id' => $instance->id
            ]);
        }


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
        dispatch(new CreateSystemPolicy($task));
    }

    private function createNetworkRules($networkPolicy): void
    {
        dispatch(new AllowLogicMonitor($networkPolicy));
    }

    private function createFirewallRules($router): void
    {
        $task = new Task([
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $task->resource()->associate($router);
        $task->save();
        dispatch(new CreateCollectorRules($task));
    }

    /**
     * @param $instance
     * @return Credential|bool
     * @throws BindingResolutionException
     */
    private function createLMCredentials($instance)
    {
        $guestAdminCredential = $instance->getGuestAdminCredentials();

        if (!$guestAdminCredential) {
            $message = get_class($this) . ' : Failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);

            return false;
        }

        list($username, $password, $hidden, $sudo) =
            ['lm.' . $instance->id, $this->passwordService->generate(24), true, false];
        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => $username,
            'resource_id' => $instance->id,
            'username' => $username,
            'is_hidden' => $hidden,
            'port' => '2020',
        ]);
        $credential->password = $password;
        $credential->save();

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

        return $credential;
    }
}
