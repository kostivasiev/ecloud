<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class Image
 * @package App\Models\V2
 */
class Image extends Model implements Filterable, Sortable, ResellerScopeable
{
    use CustomKey, SoftDeletes, DeletionRules, DefaultName, Syncable, Taskable;

    public string $keyPrefix = 'img';

    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';

    protected $casts = [
        'active' => 'boolean',
        'public' => 'boolean',
        'license_id' => 'integer'
    ];

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->attributes = [
            'visibility' => static::VISIBILITY_PRIVATE
        ];

        $this->fillable([
            'id',
            'name',
            'vpc_id',
            'reseller_id',
            'logo_uri',
            'documentation_uri',
            'description',
            'script_template',
            'vm_template',
            'platform',
            'active',
            'public',
            'visibility',
            'publisher'
        ]);
        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->vpc !== null ? $this->vpc->getResellerId() : 0;
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    /**
     * Pivot table image_availability_zone
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function availabilityZones()
    {
        return $this->belongsToMany(AvailabilityZone::class);
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
    }

    public function imageParameters()
    {
        return $this->hasMany(ImageParameter::class);
    }

    public function imageMetadata()
    {
        return $this->hasMany(ImageMetadata::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $query->where('public', true)->where('active', true);

        $query->where(function ($query) use ($user) {
            $query->whereHas('vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            });
            $query->orWhere('visibility', Image::VISIBILITY_PUBLIC);
        });

        return $query;
    }

    /**
     * @return int|null
     */
    public function getLicenseIDAttribute()
    {
        if ($this->imageMetadata()->where('key', 'ukfast.license.id')->exists()) {
            return (int) $this->imageMetadata()->where('key', 'ukfast.license.id')->first()->value;
        }

        return null;
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory): array
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('vpc_id', Filter::$stringDefaults),
            $factory->create('logo_uri', Filter::$stringDefaults),
            $factory->create('documentation_uri', Filter::$stringDefaults),
            $factory->create('description', Filter::$stringDefaults),
            $factory->create('script_template', Filter::$stringDefaults),
            $factory->create('vm_template', Filter::$stringDefaults),
            $factory->create('platform', Filter::$enumDefaults),
            $factory->create('active', Filter::$enumDefaults),
            $factory->create('public', Filter::$enumDefaults),
            $factory->create('visibility', Filter::$enumDefaults),
            $factory->create('publisher', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory): array
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('vpc_id'),
            $factory->create('logo_uri'),
            $factory->create('documentation_uri'),
            $factory->create('description'),
            $factory->create('script_template'),
            $factory->create('vm_template'),
            $factory->create('platform'),
            $factory->create('active'),
            $factory->create('public'),
            $factory->create('visibility'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory): array
    {
        return [
            $factory->create('created_at', 'desc'),
        ];
    }

    public function databaseNames(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'vpc_id' => 'vpc_id',
            'logo_uri' => 'logo_uri',
            'documentation_uri' => 'documentation_uri',
            'description' => 'description',
            'script_template' => 'script_template',
            'vm_template' => 'vm_template',
            'platform' => 'platform',
            'active' => 'active',
            'public' => 'public',
            'visibility' => 'visibility',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
