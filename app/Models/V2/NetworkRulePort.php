<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class NetworkRulePort extends Model implements Searchable, Manageable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules;

    const ICMP_MESSAGE_TYPE_ECHO_REQUEST = 8;
    public string $keyPrefix = 'nrp';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'network_rule_id',
            'name',
            'protocol',
            'source',
            'destination',
        ];
        parent::__construct($attributes);
    }

    public function networkRule(): BelongsTo
    {
        return $this->belongsTo(NetworkRule::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }

        return $query
            ->whereHas('networkRule.networkPolicy.network.router.vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            })
            ->whereHas('networkRule.networkPolicy.network.router', function ($query) {
                $query->where('is_management', false);
            });
    }

    public function isManaged() :bool
    {
        return (bool) $this->networkRule->networkPolicy->router->isManaged();
    }

    public function isHidden(): bool
    {
        return $this->isManaged();
    }

    public function locked(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->networkRule->networkPolicy->locked,
        );
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'network_rule_id' => $filter->string(),
            'protocol' => $filter->string(),
            'source' => $filter->string(),
            'destination' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
