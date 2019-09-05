<?php

namespace App\Models\V1;

use App\Scopes\SanServersScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Class San
 * Subset model for servers table of servers_type = 'san'
 * @package App\Models\V1
 */
class San extends Model
{
    protected $table = 'servers';

    protected $primaryKey = 'servers_id';

    public $timestamps = false;

    public const SAN_USERNAME = 'apiuser';

    /**
     * The "booting" method of the model.
     * Apply a scope/filter to ** ALL ** Queries using this model of 'servers_type', '=', 'san'
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new SanServersScope());
    }

    /**
     * Return the SAN name
     */
    public function name()
    {
        return $this->servers_netnios_name;
    }

    /**
     * Map to ucs_storage
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function storage()
    {
        return $this->belongsTo(
            Storage::class,
            'servers_id',
            'server_id'
        );
    }

    /**
     * Retrieve the SAN password from the associated server details record
     * @return mixed
     */
    public function password()
    {
        return $this->hasOne(
            'App\Models\V1\ServerDetail',
            'server_detail_server_id',
            'servers_id'
        )
            ->where('server_detail_type', '=', 'API')
            ->where('server_detail_user', '=', static::SAN_USERNAME)->firstOrFail()->password();
    }
}
