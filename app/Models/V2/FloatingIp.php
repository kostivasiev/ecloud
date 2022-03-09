<?php

namespace App\Models\V2;

use App\Events\V2\FloatingIp\Created;
use App\Events\V2\FloatingIp\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class FloatingIp extends Model implements Searchable, ResellerScopeable, AvailabilityZoneable, Natable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, Syncable, Taskable;

    public $keyPrefix = 'fip';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';

    protected $fillable = [
        'id',
        'name',
        'vpc_id',
        'availability_zone_id',
        'rdns_hostname',
    ];

    protected $dispatchesEvents = [
        'deleted' => Deleted::class,
    ];

    public function getResellerId(): int
    {
        return $this->vpc->getResellerId();
    }

    public function getIPAddress(): string
    {
        return $this->ip_address;
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function sourceNat()
    {
        return $this->morphOne(Nat::class, 'translatedable', null, 'translated_id');
    }

    public function destinationNat()
    {
        return $this->morphOne(Nat::class, 'destinationable', null, 'destination_id');
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->whereHas('vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function resource()
    {
        return $this->morphTo();
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'vpc_id' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'ip_address' => $filter->string(),
            'resource_id' => $filter->string(),
            'rdns_hostname' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
