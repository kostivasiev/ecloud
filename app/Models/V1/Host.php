<?php

namespace App\Models\V1;

use App\Models\V1\Pod\Location;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Devices\AdminClient;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\SDK\Page;

class Host extends Model implements Filterable, Sortable
{
    const RESERVED_SYSTEM_RAM = 2;


    /**
     * Eloquent configuration
     * ----------------------
     */

    protected $table = 'ucs_node';
    protected $primaryKey = 'ucs_node_id';
    public $timestamps = false;


    /**
     * Ditto configuration
     * ----------------------
     */

    public static $adminProperties = [
        'ucs_node_internal_name'
    ];


    /**
     * Ditto maps raw database names to friendly names.
     * @return array
     */
    public function databaseNames()
    {
        $databaseNames = [];

        foreach ($this->properties() as $property) {
            if (is_array($property)) {
                foreach ($property as $subProperty) {
                    $databaseNames[$subProperty->getFriendlyName()] = $subProperty->getDatabaseName();
                }
                continue;
            }

            $databaseNames[$property->getFriendlyName()] = $property->getDatabaseName();
        }

        return $databaseNames;
    }

    /**
     * Ditto filtering configuration
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$primaryKeyDefaults),
        ];
    }


    /**
     * Ditto sorting configuration
     * @param SortFactory $factory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
        ];
    }

    /**
     * Ditto sorting
     * @param SortFactory $sortFactory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $sortFactory)
    {
        return [
            $sortFactory->create('id', 'asc'),
        ];
    }


    /**
     * Resource configuration
     * ----------------------
     */

    /**
     * Map request property to database field
     *
     * @return array
     * @throws \UKFast\Api\Resource\Exceptions\InvalidPropertyException
     */
    public function properties()
    {
        $properties = [
            IdProperty::create('ucs_node_id', 'id'),

            IntProperty::create('ucs_node_ucs_reseller_id', 'solution_id'),
            IntProperty::create('ucs_node_datacentre_id', 'pod_id'),

            StringProperty::create('ucs_specification_friendly_name', 'name'),


            'cpu' => [
                IntProperty::create('ucs_specification_cpu_qty', 'qty'),
                IntProperty::create('ucs_specification_cpu_cores', 'cores'),
                StringProperty::create('ucs_specification_cpu_speed', 'speed'),
            ],

            'ram' => [
                IntProperty::create('ucs_specification_ram', 'capacity'),
            ],
        ];

        $request = app('request');
        if (!$request->user()->isAdmin()) {
            return $properties;
        }

        // admin only properties
        return array_merge($properties, [
            StringProperty::create('ucs_node_internal_name', 'internal_name'),
            IntProperty::create('ucs_node_reseller_id', 'reseller_id'),
            StringProperty::create('ucs_specification_name', 'specification'),
        ]);
    }

    /**
     * Model Methods
     * ----------------------
     */


    /**
     * Scope a query for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReseller($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            $query->where('ucs_node_reseller_id', $resellerId);
        }

        return $query;
    }

    public function scopeWithSolution($query, $solutionId)
    {
        $solutionId = filter_var($solutionId, FILTER_SANITIZE_NUMBER_INT);

        $query->where('ucs_node_ucs_reseller_id', $solutionId);
        return $query;
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
            'ucs_node_datacentre_id'
        );
    }

    /**
     * Returns the SOlution that the host belongs to
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function solution()
    {
        return $this->hasOne(
            'App\Models\V1\Solution',
            'ucs_reseller_id',
            'ucs_node_ucs_reseller_id'
        );
    }

    /**
     * Get the RAM from the host specification
     * todo: can we use ucs_specification_ram?
     *
     * @return mixed]
     */
    public function getRamSpecification()
    {
        $specificationName = $this->hasOne(
            'App\Models\V1\HostSpecification',
            'ucs_specification_id',
            'ucs_node_specification_id'
        )
            ->select('ucs_specification_name')
            ->pluck('ucs_specification_name')
            ->first();

        return intval(substr($specificationName, strpos($specificationName, '--') + 2));
    }

    /**
     * get VMware usage stats
     */
    public function getVmwareUsage()
    {
        if (!is_null($this->usage)) {
            return $this->usage;
        }

        try {
            $kingpin = app()->makeWith('App\Services\Kingpin\V1\KingpinService', [
                $this->pod
            ]);

            $vmwareHost = $kingpin->getHostByMac(
                $this->ucs_node_ucs_reseller_id,
                $this->ucs_node_eth0_mac
            );
        } catch (\Exception $exception) {
            throw $exception;
        }

        //collect host stats
        $ramCapacity = intval($this->ucs_specification_ram);
        $ramAllocated = 0;
        $ramReserved = static::RESERVED_SYSTEM_RAM;

        $virtualMachines = [];
        foreach ($vmwareHost->vms as $vm) {
            if ($vm->id > 0) {
                $ramAllocated += $vm->ramGB;
                $virtualMachines[] = $vm->id;
            }
        }

        return $this->usage = json_decode(json_encode([
            'ram' => [
                'capacity' => $ramCapacity,
                'reserved' => $ramReserved,
                'allocated' => $ramAllocated,
                'available' => ($ramCapacity - $ramAllocated - $ramReserved),
            ],
            'virtualMachines' => $virtualMachines
        ]));
    }

    /**
     * Rescan the host's cluster on vmware
     * @throws \Exception
     */
    public function clusterRescan()
    {
        try {
            $kingpin = app()->makeWith('App\Services\Kingpin\V1\KingpinService', [
                $this->solution->pod,
                $this->solution->ucs_reseller_type
            ]);

            if (!$kingpin->clusterRescan($this->solution->getKey())) {
                throw new \Exception('Failed to perform cluster rescan: ' . $kingpin->getLastError());
            }
        } catch (\Exception $exception) {
            throw new \Exception('Failed to perform cluster rescan ' . $exception->getMessage());
        }

        return true;
    }

    /**
     * We have 4 columns on the host record storing Fibre Channel World Wide Port Names.
     * Determine which ones are set and return as an array.
     * @return array
     */
    public function getFCWWNs()
    {
        $fcwwns = [];
        for ($i = 0; $i < 4; $i++) {
            $wwn = 'ucs_node_fc' . $i . '_wwpn';
            if (!empty($this->$wwn)) {
                $fcwwns[] = $this->$wwn;
            }
        }

        return $fcwwns;
    }

    /**
     * Call to Conjurer to get information on the UCS hosts
     * @see http://conjurer.rnd.ukfast:8443/swagger/ui/index#/Compute_v1/Compute_v1_RetrieveSolutionNode
     * @return array|mixed
     */
    public function getHardwareAttribute()
    {
        $devicesAdminClient = app()->make(AdminClient::class);

        // Get credentials using the POD "vce_server_id" (This "server" is a VM running vCenter)
        /** @var Page $response */
        $response = $devicesAdminClient->devices()->getCredentials($this->pod->ucs_datacentre_vce_server_id, 1, 1, [
            'type' => 'API',
            'user' => 'conjurerapi'
        ]);
        $conjurerCredentials = $response->getItems()[0] ?? null;
        if ($conjurerCredentials === null) {
            return []; // No credentials found
        }
        // Now we have the details lets just do another API call to get the password (This make it secure right?)
        $conjurerCredentials->password = $devicesAdminClient->credentials()->getPassword($conjurerCredentials->id);

        // Now for fun lets do the whole process again to get another set of details that we need
        /** @var Page $response */
        $response = $devicesAdminClient->devices()->getCredentials($this->pod->ucs_datacentre_vce_server_id, 1, 1, [
            'type' => 'API',
            'user' => 'ucs-api'
        ]);
        $podCredentials = $response->getItems()[0] ?? null;
        if ($podCredentials === null) {
            return []; // No credentials found
        }
        $podCredentials->password = $devicesAdminClient->credentials()->getPassword($podCredentials->id);

        // Get the hosts pod
        if ($this->pod === null) {
            return []; // No pod found
        }

        // Get the hosts location
        if ($this->location === null) {
            return []; // No location found
        }

        // Sanity check the host is in a location within the pod it lives in
        if ($this->pod->ucs_datacentre_id !== $this->location->pod->ucs_datacentre_id) {
            Log::error('The host ' . $this->ucs_node_id . ' is in a location outside its current pod');
            return []; // The hosts location does not belong in the hosts pod
        }

        // Assign UCS values to Conjurer variables so I don't go mad working out what is used below
        $compute = $this->location->ucs_datacentre_location_name;
        $solution = $this->ucs_node_ucs_reseller_id;
        $node = $this->ucs_node_profile_id;
        $conjurerUrl = $this->pod->ucs_datacentre_ucs_api_url;

        // Add the port number we got from the devices API to the Conjurer URL (wtf?)
        $conjurerUrl = empty($conjurerCredentials->loginPort) ? $conjurerUrl : $conjurerUrl . ':' . $conjurerCredentials->loginPort;

        // Finally do the call to Conjurer using the credentials we retrieved from the DB and devices API
        $client = app()->makeWith(Client::class, [
            'config' => [
                'base_uri' => $conjurerUrl,
                'timeout' => 10,
                'verify' => app()->environment() === 'production',
            ]
        ]);

        $response = $client->request(
            'GET',
            '/api/v1/compute/' . urlencode($compute) . '/solution/' . (int)$solution . '/node/' . urlencode($node),
            [
                'auth' => ['conjurerapi', $conjurerCredentials->password],
                'headers' => [
                    'X-UKFast-Compute-Username' => 'ucs-api',
                    'X-UKFast-Compute-Password' => $podCredentials->password,
                    'Accept' => 'application/json',
                ],
            ]
        );
        $responseObj = json_decode($response->getBody()->getContents());
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        // Now since we use snake case in our API and camel case in our other API we need to convert from one to another
        $interfaces = [];
        if (isset($responseObj->interfaces)) {
            foreach ($responseObj->interfaces as $interface) {
                $interfaces[] = [
                    'name' => $interface->name ?? '',
                    'address' => $interface->address ?? '',
                    'type' => $interface->type ?? '',
                ];
            }
        }

        return [
            'associate_state' => $responseObj->associated ?? '',
            'configuration_state' => $responseObj->configurationState ?? '',
            'power_state' => $responseObj->powerState ?? '',
            'location' => $responseObj->location ?? '',
            'assign_state' => $responseObj->assigned ?? '',
            'specification' => $responseObj->specification ?? '',
            'name' => $responseObj->name ?? '',
            'interfaces' => $interfaces ?? '',
            'hardwareVersion' => $responseObj->hardwareVersion ?? '',
        ];
    }

    /**
     * This join makes no sense to me... `ucs_datacentre` is a Pod and so in turn `ucs_datacentre_location` is a
     * physical location within a pod. So why is a host directly joining on the location that could be in any pod?
     * The fact that locations within a pod are referenced directly leads me to question the design.
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function location()
    {
        return $this->hasOne(Location::class, 'ucs_datacentre_location_id', 'ucs_node_location_id');
    }
}
