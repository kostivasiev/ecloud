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
    const NAME_FORMAT_DESC = 'alphanumeric, spaces, hyphens and underscores';
    const NAME_FORMAT_REGEX = '^[A-Za-z0-9-_\ \.]';

    const HOSTNAME_FORMAT_DESC = 'alphanumeric (start/end), hyphens and full stop';
    const HOSTNAME_FORMAT_REGEX = '^(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$';

    const NETBIOS_FORMAT_DESC = 'alphanumeric (start/end) and hyphens, upto 15 characters';
    const NETBIOS_FORMAT_REGEX = '^(?!-)[a-zA-Z0-9-]{1,15}(?<!-)$';

    // For Linux we want the min CPU's to be 1, but 2 for windows.
    const MIN_CPU = 1;
    const MAX_CPU = 10;

    const MIN_RAM = 1;
    const MAX_RAM = 30;

    const MIN_HDD = 20;
    const MIN_HDD_LINUX = 20;
    const MIN_HDD_WINDOWS = 40;

    const MAX_HDD = 300;
    const MAX_HDD_COUNT = 20;

    protected $attributes = array(
        'servers_use_ip_management' => 'Yes',
    );

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
            'ram' => 'servers_memory',
            'hdd' => 'servers_hdd',
            'platform' => 'servers_platform',
            'backup' => 'servers_backup',
            'support' => 'servers_advanced_support',
            'environment' => 'servers_ecloud_type'
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
            $factory->create('ram', Filter::$numericDefaults),
            $factory->create('hdd', Filter::$stringDefaults),
            $factory->create('platform', Filter::$stringDefaults),
            $factory->create('backup', Filter::$stringDefaults),
            $factory->create('support', Filter::$stringDefaults),
            $factory->create('environment', Filter::$stringDefaults),
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
            $factory->create('ram', 'asc'),
            $factory->create('hdd', 'asc'),
            $factory->create('platform', 'asc'),
            $factory->create('backup', 'asc'),
            $factory->create('support', 'asc'),
            $factory->create('environment', 'asc'),
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
            IntProperty::create('servers_ecloud_ucs_reseller_id', 'solution_id'),

            StringProperty::create('servers_friendly_name', 'name'),
            StringProperty::create('servers_hostname', 'hostname'),
            StringProperty::create('servers_netnios', 'computername'),

            IntProperty::create('servers_cpu', 'cpu'),
            IntProperty::create('servers_memory', 'ram'),
            IntProperty::create('servers_hdd', 'hdd'),

            StringProperty::create('ip_internal', 'ip_internal'),
            StringProperty::create('ip_external', 'ip_external'),

            StringProperty::create('servers_platform', 'platform'),
            StringProperty::create('server_license_friendly_name', 'operating_system'),

            StringProperty::create('servers_backup', 'backup'),
            StringProperty::create('servers_advanced_support', 'support'),

            StringProperty::create('servers_status', 'status'),
            StringProperty::create('servers_ecloud_type', 'environment'),
        ];

        return $array;
    }

    /**
     * Always join subtype when querying the model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function query()
    {
        return parent::query()
            ->leftjoin('server_subtype', 'server_subtype_id', '=', 'servers_subtype_id');
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
     * Scope a query to only include VMs for a given solution
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $solutionId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithSolutionId($query, $solutionId)
    {
        return $query->where('servers_ecloud_ucs_reseller_id', $solutionId);
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
            'App\Models\V1\ServerLicense',
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
     * Return Solution
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function solution()
    {
        return $this->hasOne(
            'App\Models\V1\Solution',
            'ucs_reseller_id',
            'servers_ecloud_ucs_reseller_id'
        );
    }

    /**
     * Return Pod
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pod()
    {
        return $this->hasOne(
            'App\Models\V1\Pod',
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
    public function getPod()
    {
        return $this->pod;
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
            $this->getPod(),
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
            $this->getPod(),
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
            $this->getPod(),
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

    /**
     * VM is currently being built
     * @return bool
     */
    public function isBuilding()
    {
        if (in_array($this->servers_status, array(
            'Awaiting OS Installation',
            'Initialising',
            'Being Built',
            'Customising OS',
            'Rebooting',
            'Install Software Packages',
            'Update Installed Software',
            'Configuring Network',
            'Configuring Backup',
        ))) {
            return true;
        }

        //build also includes resizing steps
        if ($this->isResizing()) {
            return true;
        }

        return false;
    }

    /**
     * VM is currently resizing
     * @return bool
     */
    public function isResizing()
    {
        if (in_array($this->servers_status, array(
            'Reconfigure VM',
            'Configuring RAM',
            'Configuring CPU/RAM',
            'Configuring CPU',
            'Configuring HDD',
            'Configuring HDD IOPS',

        ))) {
            return true;
        }

        return false;
    }

    /**
     * VM is involved in a clone process
     * @return bool
     */
    public function isCloning()
    {
        if (in_array($this->servers_status, array(
            'Cloning from Existing',
            'Cloning To Template',

        ))) {
            return true;
        }

        return false;
    }

    /**
     * VM is in process of deleting
     * @return bool
     */
    public function isDeleting()
    {
        if (in_array($this->servers_status, array(
            'Deleting',
            'Pending Deletion'
        ))) {
            return true;
        }

        return false;
    }

    /**
     * is VM in a deletable state
     * @return bool
     */
    public function canBeDeleted()
    {
        if ($this->isBuilding() || $this->isCloning() || $this->isDeleting()) {
            return false;
        }

        return true;
    }

    /**
     * Is the vm a contracted server
     *
     * @return boolean is contracted server
     */
    public function isContract()
    {
        return ($this->servers_billing_type == 'Contract');
    }

    /**
     * Is this VM in contract
     * @return boolean is eCloud VM in contract
     */
    public function inContract()
    {
        if ($this->type() != 'Public' || !$this->isContract()) {
            return false;
        }

        $contract_end_date = new DateTime($this->servers_contract_end_date);
        $current_date = new DateTime();

        if ($contract_end_date < $current_date) {
            return false;
        }

        return true;
    }

    /**
     * Is the vm a managed device
     *
     * @return boolean
     */
    public function isManaged()
    {
        $managedDevices =  array(
            'UKFast Load Balancer',
            'UKFast Web Application firewall'
        );

        return in_array($this->servers_model, $managedDevices)
            || $this->isClusteredDevice()
            || $this->isFirewall()
            || $this->isWebcelerator();
    }

    /**
     * Is this a clustered device?
     * @return bool
     */
    public function isClusteredDevice()
    {
        return (in_array($this->servers_role, array(
            'MSSQL Cluster', 'MySQL Cluster', 'File Cluster'
        )));
    }

    /**
     * is this actually a firewall?
     * @return bool
     */
    public function isFirewall()
    {
        return in_array($this->servers_type, array(
            'firewall', 'virtual firewall'
        ));
    }

    /**
     * is the server a webcelerator appliance
     *
     * @return bool
     */
    public function isWebcelerator()
    {
        return (
            $this->servers_model == 'UKFast Web Accelerator' ||
            $this->server_subtype_name == 'Webcelerator' ||
            $this->servers_role == 'Webcelerator Appliance'
        );
    }
}
