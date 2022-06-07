<?php

namespace App\Rules\V2\FirewallRulePort;

use App\Models\V2\FirewallRulePort;
use App\Models\V2\NetworkRulePort;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Request;

class UniquePortListRule implements Rule
{
    public FirewallRulePort|NetworkRulePort $model;
    public string $class;
    public string $parentKeyColumn;
    public ?string $parentId;
    public ?string $source;
    public ?string $destination;
    public ?string $protocol;

    public function __construct(string $class)
    {
        $this->class = $class;
        $this->model = new $class;
        $this->parentKeyColumn = match ($class) {
            NetworkRulePort::class => 'network_rule_id',
            default => 'firewall_rule_id',
        };
        $this->parentId = Request::input($this->parentKeyColumn, null);
        if (Request::method() == 'PATCH') {
            $this->parentId = match ($class) {
                NetworkRulePort::class => Request::route('networkRulePortId'),
                default => Request::route('firewallRulePortId')
            };
        }
        $this->protocol = Request::input('protocol', null);
    }

    public function passes($attribute, $value)
    {
        return $this->model->where(function ($query) use ($attribute, $value) {
            $query->where($this->parentKeyColumn, '=', $this->parentId);
            $query->where(function ($query) use ($attribute, $value) {
                $iteration = 0;
                foreach (explode(',', $value) as $item) {
                    if ($iteration == 0) {
                        $query->where($attribute, $item);
                        $iteration++;
                        continue;
                    }
                    $query->orWhere($attribute, $item);
                }
            });
        })->count() == 0;
    }

    public function message()
    {
        return 'There are port(s) conflicting with existing rules in this configuration';
    }
}
