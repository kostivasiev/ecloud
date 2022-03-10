<?php

namespace App\Models\V2;

use App\Events\V2\Nic\Created;
use App\Events\V2\Nic\Creating;
use App\Events\V2\Nic\Deleting;
use App\Events\V2\Nic\Saved;
use App\Events\V2\Nic\Saving;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class SshKeyPair extends Model implements Searchable, ResellerScopeable
{
    use HasFactory, CustomKey, DefaultName, SoftDeletes;

    public $keyPrefix = 'ssh';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'reseller_id',
        'name',
        'public_key',
    ];

    public function getResellerId(): int
    {
        return $this->reseller_id;
    }

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    /**
     * @param $query
     * @param Consumer $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->where('reseller_id', '=', $user->resellerId());
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
