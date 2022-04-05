<?php

namespace App\Console\Commands\LogicMonitor;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use App\Models\V2\Router;
use Illuminate\Console\Command;
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
    protected $signature = 'lm:register-instances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $policyRules = null;

    public function __construct()
    {
        parent::__construct();

        $this->policyRules = config('firewall.system.rules');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(MonitoringAdminClient $adminMonitoringClient, AccountAdminClient $accountAdminClient)
    {
        $routers = Router::withoutTrashed()->get();

        foreach ($routers as $router) {
            $network = $router->network();

            // check 'system' policy exists, if not then create
            /** @var FirewallPolicy $firewallPolicy */
            $systemPolicy = $router->whereHas('firewallPolicies', function ($query) {
                $query->where('name', '=', 'System');
            })->first();

            if (!$systemPolicy) {
                $this->createSystemPolicy($router);
            }

            // check for collector in routers AZ, if none then skip
            $client = $adminMonitoringClient
                ->setResellerId($router->getResellerId());

            //check for collector in routers AZ, if none then skip
            $collectors = $client->collectors()->getAll([
                'datacentre_id' => $router->availabilityZone->datacentre_site_id,
                'is_shared' => true,
            ]);

            if (empty($collectors)) {
                $this->info('No Collector found for datacentre', [
                    'availability_zone_id' => $router->availabilityZone->id,
                    'network_id' => $network->id,
                    'router_id' => $router->id,
                    'datacentre_site_id' => $router->availabilityZone->datacentre_site_id,
                ]);

                break;
            }

            // check LM rule exists, if does then skip
            if (!$this->hasRule($firewallPolicy, $this->policyRules[0])) {
                foreach ($this->policyRules as $rule) {
                    $firewallRule = app()->make(FirewallRule::class);
                    $firewallRule->fill($rule);
                    $firewallRule->source = $ipAddresses;
                    $firewallRule->firewallPolicy()->associate($firewallPolicy);
                    $firewallRule->save();

                    foreach ($rule['ports'] as $port) {
                        $firewallRulePort = app()->make(FirewallRulePort::class);
                        $firewallRulePort->fill($port);
                        $firewallRulePort->firewallRule()->associate($firewallRule);
                        $firewallRulePort->save();
                    }
                    $firewallPolicy->syncSave();
                }
            }

            // create LM rule to open ports inbound from collectors IP
            foreach ($network->policies() as $policy) {
                $ipAddresses = [];
                foreach ($collectors as $collector) {
                    $ipAddresses[] = $collector->ipAddress;
                }
                $ipAddresses = implode(',', $ipAddresses);
                $rules = config('firewall.system.rules');

                if (!$this->hasRule($policy, $rules[0])) {
                    foreach ($rules as $rule) {
                        $networkRule = app()->make(NetworkRule::class);
                        $networkRule->fill($rule);
                        $networkRule->source = $ipAddresses;
                        $network->networkRules()->save($networkRule);

                        foreach ($rule['ports'] as $port) {
                            $networkRulePort = app()->make(NetworkRulePort::class);
                            $networkRulePort->fill($port);
                            $networkRulePort->networkRule()->associate($networkRule);
                            $networkRulePort->save();
                        }
                    }
                }
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
                //TODO: Create LogicMonitorCredentials
                Log::error(new \Exception('Failed to load logic monitor credentials for instance ' . $instance->id));
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

    protected function saveLogicMonitorAccountId($id, $instance)
    {
        $deploy_data = $instance->deploy_data;
        $deploy_data['logic_monitor_account_id'] = $id;
        $instance->deploy_data = $deploy_data;

        return $instance->save();
    }

    private function createSystemPolicy($router)
    {
        $policyConfig = config('firewall.system');

        $systemPolicy = app()->make(FirewallPolicy::class);
        $systemPolicy->fill($policyConfig);
        $systemPolicy->router_id = $router->id;
        $systemPolicy->syncSave();

        return $systemPolicy;
    }

    /**
     * @param FirewallPolicy $firewallPolicy
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function hasRule(FirewallPolicy $firewallPolicy, $rule)
    {
        return $firewallPolicy->where($rule)->count() > 0;
    }
}
