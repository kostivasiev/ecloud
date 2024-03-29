<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class FirewallRulePort
 * @package App\Models\V2
 * @method static findOrFail(string $firewallRuleId)
 * @method static forUser($request)
 */
class FirewallRulePort extends Model implements Searchable, Manageable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName;

    const ICMP_MESSAGE_TYPE_ECHO_REQUEST = 8;
    public $keyPrefix = 'fwrp';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'name',
        'firewall_rule_id',
        'protocol',
        'source',
        'destination'
    ];

    public function firewallRule()
    {
        return $this->belongsTo(FirewallRule::class);
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->whereHas('firewallRule.firewallPolicy.router.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        })
            ->whereHas('firewallRule.firewallPolicy.router', function ($query) {
                $query->where('is_management', false);
            });
    }

    public function isManaged() :bool
    {
        return $this->firewallRule->firewallPolicy->router->isManaged() || $this->firewallRule->firewallPolicy->type == FirewallPolicy::TYPE_SYSTEM;
    }

    public function isHidden(): bool
    {
        return $this->isManaged() && $this->firewallRule->firewallPolicy->type != FirewallPolicy::TYPE_SYSTEM;
    }

    public function isSystem(): bool
    {
        return $this->firewallRule->isSystem();
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'firewall_rule_id' => $filter->string(),
            'source' => $filter->string(),
            'destination' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
