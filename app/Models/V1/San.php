<?php

namespace App\Models\V1;

use App\Scopes\SanServersScope;
use Illuminate\Database\Eloquent\Model;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\StringProperty;

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
     * Ditto maps raw database names to friendly names.
     * @return array
     */
    public function databaseNames()
    {
        return [
            'id' => 'servers_id',
            'name' => 'servers_netnios_name'
        ];
    }

    /**
     * Resource package
     * Map request property to database field
     *
     * @return array
     * @throws \UKFast\Api\Resource\Exceptions\InvalidPropertyException
     */
    public function properties()
    {
        return [
            IdProperty::create('servers_id', 'id'),
            StringProperty::create('servers_netnios_name', 'name')
        ];
    }

    /**
     * Ditto sorting configuration
     * @param SortFactory $factory
     * @return array
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name')
        ];
    }

    /**
     * Ditto sorting
     * @param SortFactory $sortFactory
     * @return array
     */
    public function defaultSort(SortFactory $sortFactory)
    {
        return [
            $sortFactory->create('name', 'asc'),
        ];
    }

    /**
     * Ditto Selectable persistent Properties
     * @return array
     */
    public function persistentProperties()
    {
        return ['id'];
    }

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
     *
     * NOTE: this may return multiple records as pods can share the same SAN,
     * You may want to use scopeWithPod to limit to a Pod
     * e.g. $san->storage()->withPod($volumeSet->solution->pod)->firstOrFail()
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function storage()
    {
        return $this->hasMany(
            Storage::class,
            'server_id',
            'servers_id'
        );
    }

    /**
     * Retrieve the SAN password from the associated server details record
     * @return mixed
     */
    public function getPassword()
    {
        return $this->hasOne(
            'App\Models\V1\ServerDetail',
            'server_detail_server_id',
            'servers_id'
        )
            ->where('server_detail_type', '=', 'API')
            ->where('server_detail_user', '=', static::SAN_USERNAME)->firstOrFail()->getPassword();
    }
}
