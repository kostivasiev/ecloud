<?php

namespace App\Listeners\V2\FirewallPolicy;

use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param \App\Events\V2\FirewallPolicy\Saved|\App\Events\V2\FirewallRule\Saved $event
     * @return void
     * @throws \Exception
     */
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $policy = $event->model;

        if ($event->model instanceof FirewallRule) {
            $policy = $event->model->firewallPolicy;
        }
        if ($event->model instanceof FirewallRulePort) {
            $policy = $event->model->firewallRule->firewallPolicy;
        }
        if (!$policy) {
            $message = 'Deploy called with invalid policy';
            Log::error($message, [
                'event' => $event,
            ]);
            $this->fail(new \Exception($message));
            return;
        }
        dispatch(new \App\Jobs\FirewallPolicy\Deploy([
            'policy_id' => $policy->id,
        ]));

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
