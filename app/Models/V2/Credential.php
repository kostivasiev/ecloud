<?php

namespace App\Models\V2;

use App\Events\V2\Credential\Creating;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class Credentials
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 * @method static forUser(string $user)
 */
class Credential extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName;

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
        return $this->belongsTo(Instance::class, 'resource_id', 'id');
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

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(function ($filter) {
            $filters = [
                'id' => $filter->string(),
                'name' => $filter->string(),
                'resource_id' => $filter->string(),
                'host' => $filter->string(),
                'username' => $filter->string(),
                'password' => $filter->string(),
                'port' => $filter->string(),
                'created_at' => $filter->date(),
                'updated_at' => $filter->date(),
            ];
            if (Auth::user()->isAdmin()) {
                $filters['is_hidden'] = $filter->numeric();
            }
            return $filters;
        });
    }
}
