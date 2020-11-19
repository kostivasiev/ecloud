<?php
namespace App\Listeners\V2\FirewallPolicy\FirewallRule\FirewallRulePort;

use App\Events\V2\FirewallRule\Deleted;
use App\Models\V2\FirewallRule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Delete implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);
        $firewallRule = FirewallRule::withTrashed()->findOrFail($event->model->getKey());
        $firewallRule->firewallRulePorts()->each(function ($ports) {
            $ports->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
