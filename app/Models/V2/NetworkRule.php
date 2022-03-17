<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class NetworkRule extends Model implements Searchable, Manageable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules;

    public string $keyPrefix = 'nr';

    const TYPE_DHCP = 'dhcp';
    const TYPE_CATCHALL = 'catchall';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'network_policy_id',
            'name',
            'sequence',
            'source',
            'destination',
            'action',
            'direction',
            'enabled',
            'type'
        ];
        $this->casts = [
            'sequence' => 'integer',
            'enabled' => 'boolean',
        ];
        parent::__construct($attributes);
    }

    public function networkPolicy()
    {
        return $this->belongsTo(NetworkPolicy::class);
    }

    public function networkRulePorts()
    {
        return $this->hasMany(NetworkRulePort::class);
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
            ->whereHas('networkPolicy.network.router.vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            })
            ->whereHas('networkPolicy.network.router', function ($query) {
                $query->where('is_management', false);
            });
    }

    public function isManaged() :bool
    {
        return (bool) $this->networkPolicy->network->router->isManaged();
    }

    public function isHidden(): bool
    {
        return $this->isManaged();
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'network_policy_id' => $filter->string(),
            'name' => $filter->string(),
            'sequence' => $filter->numeric(),
            'source' => $filter->string(),
            'destination' => $filter->string(),
            'action' => $filter->string(),
            'direction' => $filter->string(),
            'enabled' => $filter->numeric(),
            'type' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
