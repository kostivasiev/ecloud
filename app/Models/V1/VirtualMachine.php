<?php

namespace App\Models\V1;

use App\Exceptions\V1\KingpinException;
use Illuminate\Database\Eloquent\Model;

use App\Scopes\ECloudVmServersScope;

use UKFast\Api\Resource\Property\StringProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\IdProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

/**
 * Class VirtualMachine
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @package App\Models\V1
 */
class VirtualMachine extends Model implements Filterable, Sortable
{
    /**
     * Cast our database columns to the correct data type.
     *
     * @var array
     */
    protected $casts = [
        'servers_id' => 'integer',
        'servers_friendly_name' => 'string',
        'servers_cpu' => 'string',
        'servers_memory' => 'string',
        'servers_hdd' => 'string',
        'servers_platform' => 'string',
        'servers_backup' => 'string',
        'servers_advanced_support' => 'string'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'servers';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'servers_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The "booting" method of the model.
     * Apply a scope/filter to ** ALL ** Queries using this model of 'servers_type', '=', 'ecloud vm'
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ECloudVmServersScope);
    }

    /**
     * Ditto configuration
     * ----------------------
     */

    /**
     * Ditto maps raw database names to friendly names.
     * @return array
     */
    public function databaseNames()
    {
        return [
            'id' => 'servers_id',
            'name' => 'servers_friendly_name',
            'cpu' => 'servers_cpu',
            'ram_gb' => 'servers_memory',
            'hdd_gb' => 'servers_hdd',
            'platform' => 'servers_platform',
            'backup' => 'servers_backup',
            'support' => 'servers_advanced_support',
            'type' => 'servers_ecloud_type'
        ];
    }

    /**
     * Ditto filtering configuration
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns($factory)
    {
        return [
            $factory->create('id', Filter::$primaryKeyDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('cpu', Filter::$stringDefaults),
            $factory->create('ram_gb', Filter::$numericDefaults),
            $factory->create('hdd_gb', Filter::$stringDefaults),
            $factory->create('platform', Filter::$stringDefaults),
            $factory->create('backup', Filter::$stringDefaults),
            $factory->create('support', Filter::$stringDefaults),
            $factory->create('type', Filter::$stringDefaults),
        ];
    }

    /**
     * Ditto sorting configuration
     * @param SortFactory $factory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns($factory)
    {
        return [
            $factory->create('id', 'asc'),
            $factory->create('name', 'asc'),
            $factory->create('cpu', 'asc'),
            $factory->create('ram_gb', 'asc'),
            $factory->create('hdd_gb', 'asc'),
            $factory->create('platform', 'asc'),
            $factory->create('backup', 'asc'),
            $factory->create('support', 'asc'),
            $factory->create('type', 'asc'),
        ];
    }

    /**
     * Ditto sorting
     * @param SortFactory $factory
     * @return mixed
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort($factory)
    {
        return $factory->create('id', 'asc');
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
     * End Ditto configuration
     * ----------------------
     */

    /**
     * Resource package
     * Map request property to database field
     *
     * @return array
     */
    public function properties()
    {
        $array = [
            IdProperty::create('servers_id', 'id'),
            StringProperty::create('servers_friendly_name', 'name'),
            IntProperty::create('servers_cpu', 'cpu'),
            IntProperty::create('servers_memory', 'ram_gb'),
            IntProperty::create('servers_hdd', 'hdd_gb'),
            StringProperty::create('servers_platform', 'platform'),
            StringProperty::create('server_license_friendly_name', 'operating_system'),
            StringProperty::create('servers_backup', 'backup'),
            StringProperty::create('servers_advanced_support', 'support'),
            StringProperty::create('ip_internal', 'ip_internal'),
            StringProperty::create('ip_external', 'ip_external'),
            StringProperty::create('servers_ecloud_type', 'type')
        ];

        // Add solution_id for non Public VM's
        if ($this->attributes['servers_ecloud_type'] != 'Public') {
            $array[] = IntProperty::create('servers_ecloud_ucs_reseller_id', 'solution_id');
        }

        return $array;
    }

    /**
     * Scope a query to only include firewalls for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithResellerId($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            $query->where('servers_reseller_id', $resellerId);
        }

        return $query;
    }

    /**
     * Mutate the servers_cpu attribute to just the number of cpu's
     * @param $value
     * @return int
     */
    public function getServersCpuAttribute($value)
    {
        $cpuNum = 0;

        $str = trim(substr($value, 0, 2));
        if (is_numeric($str)) {
            $cpuNum = intval($str);
            return $cpuNum;
        }

        $str = trim(substr($value, 0, 1));
        if (is_numeric($str)) {
            $cpuNum = intval($str);
        }

        return $cpuNum;
    }

    /**
     * Mutate the server's memory attribute to an integer
     * @param $value
     * @return float|int
     */
    public function getServersMemoryAttribute($value)
    {
        $ramGB = 0;

        $str = trim(substr($value, 0, 2));
        if (is_numeric($str)) {
            $ramGB = intval($str);
            return $ramGB;
        }

        $str = trim(substr($value, 0, 1));
        if (is_numeric($str)) {
            $ramGB = intval($str);
        }

        if (stripos($value, 'MB') !== false) {
            $ramGB = $ramGB / 1024;
        }

        return $ramGB;
    }

    /**
     * Mutate the server's hdd attribute to an integer
     * @param $value
     * @return float|int
     */
    public function getServersHddAttribute($value)
    {
        $hddGB = 0;

        $str = trim(substr($value, 0, 4));
        if (is_numeric($str)) {
            $hddGB = intval($str);
            return $hddGB;
        }

        $str = trim(substr($value, 0, 3));
        if (is_numeric($str)) {
            $hddGB = intval($str);
            return $hddGB;
        }

        $str = trim(substr($value, 0, 2));
        if (is_numeric($str)) {
            $hddGB = intval($str);
            return $hddGB;
        }

        $str = trim(substr($value, 0, 1));
        if (is_numeric($str)) {
            $hddGB = intval($str);
        }

        return $hddGB;
    }

    /**
     * Relation Mappings
     */

    /**
     * Map a server_license to the Virtual Machine
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function license()
    {
        return $this->hasOne(
            'App\Models\V1\ServerLicence',
            'server_license_name',
            'servers_license'
        )->select(
            'server_license_id',
            'server_license_friendly_name'
        );
    }

    /**
     * Map a server_license to the Virtual Machine
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function serverIPAddress()
    {
        return $this->hasMany(
            'App\Models\V1\ServerIPAddress',
            'server_ip_address_server_id',
            'servers_id'
        )->select(
            'server_ip_address_id',
            'server_ip_address_internal_ip',
            'server_ip_address_external_ip',
            'server_ip_address_active'
        );
    }

    /**
     * Map the UCS reseller id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function UCSReseller()
    {
        return $this->hasOne(
            'App\Models\V1\UCSReseller',
            'ucs_reseller_id',
            'servers_ecloud_ucs_reseller_id'
        );
    }


    /**
     * Map a UCSDatacentre to the Virtual Machine
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function UCSDatacentre()
    {
        return $this->hasOne(
            'App\Models\V1\UCSDatacentre',
            'ucs_datacentre_id',
            'servers_ecloud_datacentre_id'
        );
    }


    /**
     * Non-database / server_table attributes
     * @var array
     */
    protected $appends = [
        'server_license_friendly_name',
        'ip_internal',
        'ip_external',
    ];

    /**
     * Creates the server_license_friendly_name attribute
     * @return null
     */
    public function getServerLicenseFriendlyNameAttribute()
    {
        if (!empty($this->license)) {
            return !empty($this->license->server_license_friendly_name) ?
                $this->license->server_license_friendly_name : null;
        }
    }

    /**
     * Append ip_internal attribute
     * @return null
     */
    public function getIpInternalAttribute()
    {
        // Load the IP from the server_ip_address table
        $internalIP = null;
        // Use IP Management?
        if ($this->attributes['servers_use_ip_management'] == 'Yes') {
            $serverIPAddress = $this->serverIPAddress->where('server_ip_address_active', '=', 'Yes')->first();

            if ($serverIPAddress) {
                $internalIP = $serverIPAddress->server_ip_address_internal_ip;
            }
        }

        if (empty($internalIP)) {
            // Load from the servers table,
            if (!empty($this->attributes['servers_ip'])) {
                // Check it's an internal Ip (192. or 10. ranges)
                if (preg_match('/^192\.|10\./', $this->attributes['servers_ip']) === 1) {
                    return $this->attributes['servers_ip'];
                }
            }
        }
        return $internalIP;
    }


    /**
     * Get the ip_external attribute
     * @return null
     */
    public function getIpExternalAttribute()
    {
        // Load the IP from the server_ip_address table
        $externalIp = null;

        // Use IP Management?
        if ($this->attributes['servers_use_ip_management'] == 'Yes') {
            $serverIPAddress = $this->serverIPAddress->where('server_ip_address_active', '=', 'Yes')->first();

            if ($serverIPAddress) {
                $externalIp = $serverIPAddress->server_ip_address_external_ip;
            }
        }

        if (empty($externalIp)) {
            // Load from the servers table,
            if (!empty($this->attributes['servers_ip'])) {
                // Check it's an internal Ip (192. or 10. ranges)
                if (preg_match('/^192\.|10\./', $this->attributes['servers_ip']) === 0) {
                    $externalIp = $this->attributes['servers_ip'];
                }
            }
        }
        return $externalIp;
    }

    /**
     * Get the datacentre for this VM
     * @return null
     */
    public function getDatacentre()
    {
        if (!empty($this->UCSDatacentre)) {
            return $this->UCSDatacentre;
        }

        if ($this->servers_ecloud_datacentre_id == 0) {
            if (!empty($this->UCSReseller)) {
                return $this->UCSReseller->UCSDatacentre;
            }
        }

        return false;
    }


    /**
     * Non-database functions
     */


    /**
     *  Check the current state of the VM
     * @return string
     */
    public function stateCheck()
    {
        $config = [
            $this->getDatacentre(),
            $this->type()
        ];

        try {
            $kingpin = app()->makeWith('App\Kingpin\V1\KingpinService', $config);
        } catch (\Exception $exception) {
            return 'Unknown';
        }

        $isOnline = $kingpin->checkVMOnline(
            $this->getKey(),
            $this->solutionId()
        );

        if ($isOnline === true) {
            return 'Online';
        }

        if (empty($kingpin->getLastError())) {
            return 'Offline';
        }

        return 'Unknown';
    }


    /**
     * Return the current status of the VM's vmware tools
     * @return mixed
     */
    public function vmwareToolsStatus()
    {
        $config = [
            $this->getDatacentre(),
            $this->type()
        ];

        try {
            $kingpin = app()->makeWith('App\Kingpin\V1\KingpinService', $config);
        } catch (\Exception $exception) {
            return false;
        }

        $response = $kingpin->vmwareToolsStatus(
            $this->getKey(),
            $this->solutionId()
        );

        return $response;
    }

    /**
     * Get active HDD's for the VM
     * @return array|bool
     */
    public function getActiveHDDs()
    {
        $config = [
            $this->getDatacentre(),
            $this->type()
        ];

        try {
            $kingpin = app()->makeWith('App\Kingpin\V1\KingpinService', $config);
        } catch (\Exception $exception) {
            return false;
        }

        $response = $kingpin->getActiveHDDs(
            $this->getKey(),
            $this->solutionId()
        );

        return $response;
    }


    /**
     * Return the VM solutionId
     * @return mixed
     */
    public function solutionId()
    {
        if ($this->servers_ecloud_type == 'Public') {
            return null;
        }

        return $this->servers_ecloud_ucs_reseller_id;
    }

    /**
     * Return the type of VM, Hybrid / Public etc
     * @return mixed
     */
    public function type()
    {
        return $this->servers_ecloud_type;
    }
}
