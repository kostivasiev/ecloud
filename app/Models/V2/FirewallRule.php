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
 * Class FirewallRule
 * @package App\Models\V2
 * @method static findOrFail(string $firewallRuleId)
 * @method static forUser($request)
 */
class FirewallRule extends Model implements Searchable, Manageable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName;

    public $keyPrefix = 'fwr';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'name',
        'sequence',
        'deployed',
        'firewall_policy_id',
        'source',
        'destination',
        'action',
        'direction',
        'enabled',
    ];

    protected $casts = [
        'deployed' => 'boolean',
        'enabled' => 'boolean',
    ];

    public function firewallPolicy()
    {
        return $this->belongsTo(FirewallPolicy::class);
    }

    public function firewallRulePorts()
    {
        return $this->hasMany(FirewallRulePort::class);
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }

        return $query
            ->whereHas('firewallPolicy.router.vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            })
            ->whereHas('firewallPolicy.router', function ($query) {
                $query->where('is_management', false);
            });
    }

    protected function source(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => preg_replace('/\s+/', '', $value),
            set: fn ($value) => preg_replace('/\s+/', '', $value),
        );
    }

    protected function destination(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => preg_replace('/\s+/', '', $value),
            set: fn ($value) => preg_replace('/\s+/', '', $value),
        );
    }

    public function isManaged() :bool
    {
        return (bool) $this->firewallPolicy->router->isManaged();
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
            'firewall_policy_id' => $filter->string(),
            'deployed' => $filter->numeric(),
            'source' => $filter->string(),
            'destination' => $filter->string(),
            'action' => $filter->string(),
            'direction' => $filter->string(),
            'enabled' => $filter->numeric(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
