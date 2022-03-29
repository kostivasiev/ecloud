<?php

namespace App\Models\V2;

use App\Events\V2\Volume\Creating;
use App\Events\V2\Volume\Deleted;
use App\Models\V2\Filters\VolumeAttachedFilter;
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

class Volume extends Model implements Searchable, ResellerScopeable, AvailabilityZoneable
{
    use CustomKey, SoftDeletes, DefaultName, Syncable, Taskable, HasFactory;

    public $keyPrefix = 'vol';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'vpc_id',
            'availability_zone_id',
            'capacity',
            'vmware_uuid',
            'os_volume',
            'iops',
            'is_shared',
            'volume_group_id',
            'port'
        ]);

        $this->casts = [
            'os_volume' => 'boolean',
            'is_shared' => 'boolean',
        ];

        $this->attributes = [
            'os_volume' => false,
            'is_shared' => false,
        ];

        $this->dispatchesEvents = [
            'creating' => Creating::class,
            'deleted' => Deleted::class,
        ];

        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->vpc->getResellerId();
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function instances()
    {
        return $this->belongsToMany(Instance::class)->using(InstanceVolume::class);
    }

    public function volumeGroup()
    {
        return $this->belongsTo(VolumeGroup::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }

        $query->whereHas('vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });

        return $query->where(function ($query) {
            $query->whereDoesntHave('instances', function ($query) {
                $query->where('is_hidden', '=', true);
            });
        });
    }


    /**
     * @return bool
     */
    public function getAttachedAttribute()
    {
        if ($this->instances()->count() > 0) {
            return true;
        }

        return false;
    }

    public function getTypeAttribute()
    {
        return $this->attributes['os_volume'] ? 'os' : 'data';
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'vpc_id' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'capacity' => $filter->string(),
            'vmware_uuid' => $filter->string(),
            'os_volume' => $filter->numeric(),
            'is_shared' => $filter->boolean(),
            'volume_group_id' => $filter->string(),
            'port' => $filter->numeric(),
            'iops' => $filter->numeric(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
            'attached' => $filter->for('instances')->wrap(new VolumeAttachedFilter())->boolean(),
        ]);
    }
}
