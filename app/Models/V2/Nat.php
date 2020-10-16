<?php

namespace App\Models\V2;

use App\Events\V2\Nat\Created;
use App\Events\V2\Nat\Saved;
use App\Support\Resource;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'created' => Created::class,
        'saved' => Saved::class,
    ];

    public function getRuleIdAttribute()
    {
        return $this->destination . '-to-' . $this->translated;
    }

    public function getDestinationResourceAttribute()
    {
        return Resource::classFromId($this->destination)::findOrFail($this->destination);
    }

    public function getTranslatedResourceAttribute()
    {
        return Resource::classFromId($this->translated)::findOrFail($this->translated);
    }
}
