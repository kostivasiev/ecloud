<?php

namespace App\Models\V2;

use App\Events\V2\NatCreated;
use App\Support\Resource;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * Class Nat
 *
 * @property string $destination Floating IP ID
 * @property string $translated NIC ID
 */
class Nat extends Model
{
    use CustomKey, SoftDeletes;

    public $keyPrefix = 'nat';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'destination',
        'translated',
    ];

    protected $dispatchesEvents = [
        'created' => NatCreated::class,
    ];

    public function getRuleIdAttribute()
    {
        return $this->destination . '-to-' . $this->translated;
    }

    public function getDestinationResourceAttribute()
    {
        return Resource::loadFromId($this->destination);
    }

    public function getTranslatedResourceAttribute()
    {
        return Resource::loadFromId($this->translated);
    }
}
