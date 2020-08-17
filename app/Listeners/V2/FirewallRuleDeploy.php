<?php

namespace App\Listeners\V2;

use App\Services\NsxService;
use App\Events\V2\FirewallRuleCreated;
use App\Models\V2\FirewallRule;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;

class FirewallRuleDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var NsxService
     */
    private $nsxService;

    /**
     * @param NsxService $nsxService
     * @return void
     */
    public function __construct(NsxService $nsxService)
    {
        $this->nsxService = $nsxService;
    }

    /**
     * @param FirewallRuleCreated $event
     * @return void
     * @throws \Exception
     */
    public function handle(FirewallRuleCreated $event)
    {
        /** @var FirewallRule $firewallRule */
        $firewallRule = $event->firewallRule;
        try {
            $this->nsxService->put('policy/api/v1/infra/tier-1s/' . $firewallRule->id, [
                'json' => [
                    'tier0_path' => '/infra/tier-0s/T0',
                ],
            ]);
        } catch (GuzzleException $exception) {
            $json = json_decode($exception->getResponse()->getBody()->getContents());
            throw new \Exception($json);
        }
        $firewallRule->deployed = true;
        $firewallRule->save();
    }
}
