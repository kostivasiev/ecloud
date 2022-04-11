<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class FirewallPolicy
 * @package App\Models\V2
 * @method static findOrFail(string $firewallPolicyId)
 * @method static forUser($request)
 */
class FirewallPolicy extends Model implements Searchable, ResellerScopeable, Manageable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'fwp';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'name',
            'sequence',
            'router_id',
            'locked',
        ];
        $this->attributes = [
            'locked' => false,
        ];
        $this->casts = [
            'sequence' => 'integer',
            'locked' => 'boolean',
        ];
        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->router->getResellerId();
    }

    public function firewallRules()
    {
        return $this->hasMany(FirewallRule::class);
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }

        $query->whereHas('router', function ($query) {
            $query->where('is_management', false);
        });

        return $query->whereHas('router.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function scopeSystemPolicy($query)
    {
        $query->where('name', '=', 'System');
    }

    public function isManaged() :bool
    {
        return (bool) $this->router->isManaged();
    }

    public function isHidden(): bool
    {
        return $this->isManaged();
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'sequence' => $filter->string(),
            'router_id' => $filter->string(),
            'locked' => $filter->boolean(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }

    /**
     * @param array $rules - A configuration array of rules and ports
     * @param array $ruleOverrides - keys/values to override in the rule config in $rules array
     * @param array $portOverrides - keys/values to override in the port config in $rules array
     * @return $this
     */
    public function createRulesAndPorts(
        array $rules,
        array $ruleOverrides = [],
        array $portOverrides = []
    ): self {
        foreach ($rules as $rule) {
            $firewallRule = app()->make(FirewallRule::class);
            $firewallRule->fill($rule);
            if (!empty($ruleOverrides)) {
                $firewallRule->fill($ruleOverrides);
            }
            $this->firewallRules()->save($firewallRule);
            $firewallRule->save();

            foreach ($rule['ports'] as $port) {
                $firewallRulePort = app()->make(FirewallRulePort::class);
                $firewallRulePort->fill($port);
                if (!empty($portOverrides)) {
                    $firewallRulePort->fill($portOverrides);
                }
                $firewallRulePort->firewallRule()->associate($firewallRule);
                $firewallRulePort->save();
            }
        }
        return $this;
    }
}
