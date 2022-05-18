<?php

namespace App\Rules\V2;

use App\Models\V2\FirewallRulePort;
use App\Models\V2\NetworkRulePort;
use Illuminate\Contracts\Validation\Rule;

class IsUniquePortOrRange implements Rule
{
    public FirewallRulePort|NetworkRulePort $model;
    public string $parentId;
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
        if (str_contains($value, '-')) {
            $ports = explode('-', $value);
            return $this->isDuplicatePortRange($attribute, $ports);
        }
        return $this->isDuplicatePort($attribute, $value);
    }

    public function isDuplicatePortRange($attribute, $ports): bool
    {
        $where = [
            [$attribute, '>=', $ports[0]],
            [$attribute, '<=', $ports[1]],
        ];
        if ($this->parentId != null) {
            $where[] = [$this->parentKeyColumn, '=', $this->parentId];
        }
        return !($this->model
                ->where($where)
                ->count() > 0);
    }

    public function isDuplicatePort($attribute, $port): bool
    {
        $where = [
            [$attribute, '=', $port],
        ];
        if ($this->parentId != null) {
            $where[] = [$this->parentKeyColumn, '=', $this->parentId];
        }
        return !(
            $this->model
                ->where($this->parentKeyColumn, $this->parentId)
                ->where($where)
                ->count() > 0
        );
    }

    public function message()
    {
        return ':attribute must be a unique port or range';
    }
}