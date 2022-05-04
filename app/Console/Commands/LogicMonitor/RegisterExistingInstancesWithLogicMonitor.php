<?php

namespace App\Console\Commands\LogicMonitor;

use App\Jobs\NetworkPolicy\AllowLogicMonitor;
use App\Jobs\Router\CreateCollectorRules;
use App\Jobs\Router\CreateSystemPolicy;
use App\Models\V2\Credential;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Services\V2\PasswordService;
use App\Support\Sync;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Log;
use UKFast\Admin\Account\AdminClient as AccountAdminClient;
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
    protected $signature = 'lm:register-all-instances {--T|test-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private Task $task;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        MonitoringAdminClient $adminMonitoringClient,
        AccountAdminClient $accountAdminClient
    ) {
        $networks = Network::withoutTrashed()->get();

        foreach ($networks as $network) {
            $router = $network->router;

            $this->createSystemPolicy($router);

            // check for collector in routers AZ, if none then skip
            // create LM rule to open ports inbound from collectors IP
            $this->createFirewallRules($router);
            foreach ($network->networkPolicy()->get() as $policy) {
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
                $this->info('Device ' . $instance->id . ' is already registered, skipping');
                break;
            }

            // check if LM creds exist, if not create
            $availabilityZone = $instance->availabilityZone;

            $collectorsPage = $adminMonitoringClient->collectors()->getPage(1, 15, [
                'datacentre_id' => $availabilityZone->datacentre_site_id,
                'is_shared' => true
            ]);

            if ($collectorsPage->totalItems() < 1) {
                $this->error('Failed to load logic monitor collector for availability zone ' . $availabilityZone->id);
                break;
            }

            $collector = $collectorsPage->getItems()[0];

            $logicMonitorCredentials = $instance->credentials()
                ->where('username', 'lm.' . $instance->id)
                ->first();
            if (empty($logicMonitorCredentials)) {
                if (!($logicMonitorCredentials = $this->createLMCredentials($instance))) {
                    $this->error('Logic Monitor account is not creatable', [
                        'instance' => $instance->id
                    ]);
                    break;
                }
            }
            // check account exists for customer, if not then create
            $accounts = $adminMonitoringClient->setResellerId($instance->vpc->reseller_id)->accounts()->getAll();
            if (!empty($accounts)) {
                $this->saveLogicMonitorAccountId($accounts[0]->id, $instance);
                $this->info('Logic Monitor account already exists, skipping');
            }

            dd('d');
            try {
                $customer = $accountAdminClient->customers()->getById($instance->vpc->reseller_id);
            } catch (NotFoundException) {
                $this->error('Failed to load account details for reseller_id ' . $instance->vpc->reseller_id);
                break;
            }

            $response = $adminMonitoringClient->accounts()->createEntity(new Account([
                'name' => $customer->name
            ]));

            $id = $response->getId();
            $this->saveLogicMonitorAccountId($id, $instance);
            $this->info('Logic Monitor account ' . $id . ' created for instance : ' . $instance->id);

            // check if fIP assigned, if not then skip

            $nics = $instance->nics();

            $collection = FloatingIp::where(function ($query) use ($nics) {
                $query->whereIn('resource_id', $nics->pluck('id'));

                $query->orWhereIn('resource_id', IpAddress::whereHas('nics', function ($query) use ($nics) {
                    return $query->whereIn('id', $nics->pluck('id'));
                })->pluck('id'));
            });

dd($collection);





            if (empty($instance->deploy_data['floating_ip_id'])) {
                $this->info('No floating IP assigned to the instance, skipping');
                break;
            }
            $floatingIp = FloatingIp::find($instance->deploy_data['floating_ip_id']);
            if (!$floatingIp) {
                $this->error('Failed to load floating IP for instance', [
                    'instance_id' => $instance->id,
                    'floating_ip_id' => $instance->deploy_data['floating_ip_id']
                ]);
                break;
            }

            // register device
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

            $deploy_data = $instance->deploy_data;
            $deploy_data['logic_monitor_device_id'] = $response->getId();
            $instance->deploy_data = $deploy_data;
            $instance->save();

            $this->info('Logic Monitor device registered for instance ' . $instance->id . ' : ' . $deploy_data['logic_monitor_device_id']);
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
        $this->info('Logic monitor account ID ' . $id . 'stored for instance ' . $instance->id);
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
        $this->info('Creating Collector_Rule network rule for network policy ' . $networkPolicy->id);
        if (!$this->option('test-run')) {
            dispatch(new AllowLogicMonitor($networkPolicy));
        }
    }

    private function createFirewallRules($router): void
    {
        $task = new Task([
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $task->resource()->associate($router);
        $task->save();
        $this->info('Creating Collector_Rule firewall rule for router ' . $router->id);
        if (!$this->option('test-run')) {
            dispatch(new CreateCollectorRules($task));
        }
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

        $this->info('Creating LM credentials for instance ' . $instance->id);
        if (!$this->option('test-run')) {
            $credential->save();
        }

        if (!$this->option('test-run')) {
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
        }

        return $credential;
    }
}
