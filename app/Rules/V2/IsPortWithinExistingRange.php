<?php

namespace App\Rules\V2;

use App\Models\V2\FirewallRulePort;
use App\Models\V2\NetworkRulePort;
use Illuminate\Contracts\Validation\Rule;

class IsPortWithinExistingRange implements Rule
{
    public FirewallRulePort|NetworkRulePort $model;
    public ?string $parentId;
    public string $parentKeyColumn;

    public function __construct(string $class, string $parentId = null)
    {
        $this->model = new $class;
        $this->parentId = $parentId;
        $this->parentKeyColumn = match ($class) {
            NetworkRulePort::class => 'network_rule_id',
            default => 'firewall_rule_id',
        };
    }

    public function passes($attribute, $value)
    {
        // if it's a range, then skip this test
        if (str_contains($value, '-')) {
            $ports = explode('-', $value);
            return !($this->isDuplicatePortRange($attribute, $ports[0]) && $this->isDuplicatePortRange($attribute, $ports[1]));
        }
        return !$this->isDuplicatePortRange($attribute, $value);
    }

    public function isDuplicatePortRange($attribute, $port): bool
    {
        $model = $this->model;
        if ($this->parentId != null) {
            $model->where($this->parentKeyColumn, $this->parentId);
        }
        return $model->get()
                ->filter(function ($portRule) use ($attribute, $port) {
                    if (!str_contains($portRule->$attribute, '-')) {
                        return false;
                    }
                    $range = explode('-', $portRule->$attribute);
                    return ($port >= $range[0] && $port <= $range[1]);
                })->count() > 0;
    }

    public function message()
    {
        return ':attribute is already assigned within a port range rule';
    }
}
