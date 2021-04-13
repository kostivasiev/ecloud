<?php

namespace App\Models\V2;

use App\Events\V2\Credential\Creating;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class Credentials
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 * @method static forUser(string $user)
 */
class Credential extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName;

    public $keyPrefix = 'cred';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'name',
        'resource_id',
        'host',
        'username',
        'password',
        'port',
        'is_hidden',
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
    ];

    protected $casts = [
        'port' => 'integer',
        'is_hidden' => 'boolean',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = encrypt($value);
    }

    public function getPasswordAttribute($value)
    {
        return !empty($value) ? decrypt($value) : null;
    }

    public function getUsernameAttribute($value)
    {
        return !empty($value) ? $value : null;
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class, 'id', 'resource_id');
    }

    public function instance()
    {
        return $this->belongsTo(Instance::class, 'id', 'resource_id');
    }

    /**
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public function scopeFilterHidden(Builder $query, Request $request)
    {
        if (!$request->user()->isAdmin()) {
            $query->where('is_hidden', '=', 0);
        }
        return $query;
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        $filters = [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('resource_id', Filter::$stringDefaults),
            $factory->create('host', Filter::$stringDefaults),
            $factory->create('username', Filter::$stringDefaults),
            $factory->create('password', Filter::$stringDefaults),
            $factory->create('port', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
        if (Auth::user()->isAdmin()) {
            $filters[] = $factory->create('is_hidden', Filter::$numericDefaults);
        }
        return $filters;
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('resource_id'),
            $factory->create('host'),
            $factory->create('username'),
            $factory->create('port'),
            $factory->create('is_hidden'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('name', 'asc'),
        ];
    }

    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'resource_id' => 'resource_id',
            'host' => 'host',
            'username' => 'username',
            'password' => 'password',
            'port' => 'port',
            'is_hidden' => 'is_hidden',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
