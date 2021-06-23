<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRulePort;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployTrashedRules extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(FirewallPolicy $firewallPolicy)
    {
        $this->model = $firewallPolicy;
    }

    public function handle()
    {
        $router = $this->model->router;
        $availabilityZone = $router->availabilityZone;

        $rulesResponse = $availabilityZone->nsxService()->get(
            '/policy/api/v1/infra/domains/default/gateway-policies/' . $this->model->id . '/rules'
        );
        $rulesResponseBody = json_decode($rulesResponse->getBody()->getContents());
        $rulesToRemove = [];

        foreach ($rulesResponseBody->results as $result) {
            $trashedRule = $this->model->firewallRules()->withTrashed()->find($result->id);
            if ($trashedRule != null && $trashedRule->trashed()) {
                $rulesToRemove[] = $result->id;
            }
        }

        if (count($rulesToRemove) < 1) {
            Log::debug('No rules to remove, skipping');
        }

        foreach ($rulesToRemove as $ruleToRemove) {
            Log::debug("Removing firewall rule", ['ruleToRemove' => $ruleToRemove]);

            $availabilityZone->nsxService()->delete(
                '/policy/api/v1/infra/domains/default/gateway-policies/' . $this->model->id . '/rules/' . $ruleToRemove
            );
        }
    }
}
