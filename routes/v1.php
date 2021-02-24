<?php

/**
 * v1 Routes
 */

$middleware = [
    'auth',
    'paginator-limit:' . env('PAGINATION_LIMIT')
];

$baseRouteParameters = [
    'prefix' => 'v1',
    'namespace' => 'V1',
    'middleware' => $middleware
];


/**
 * Grouping for routes based on middleware allocation
 */
$router->group($baseRouteParameters, function () use ($router) {
    /**
     * Base middleware only
     *
     * Add routes to this section that require base middleware only
     */

    // Virtual Machines's
    $router->get('vms', 'VirtualMachineController@index');
    $router->post('vms', 'VirtualMachineController@create');
    $router->get('vms/{vmId}', 'VirtualMachineController@show');
    /**
     * @deprecated
     * We are replacing the PUT /vms/{vmId} endpoint with PATCH as it's a partial resource update.
     * The PUT endpoint is to be removed once we are happy nobody is using it. Have added some logging to monitor usage.
     */
    $router->put('vms/{vmId}', 'VirtualMachineController@update');
    $router->patch('vms/{vmId}', 'VirtualMachineController@update');
    $router->delete('vms/{vmId}', 'VirtualMachineController@destroy');

    $router->post('vms/{vmId}/clone', 'VirtualMachineController@clone');
    $router->post('vms/{vmId}/clone-to-template', 'VirtualMachineController@cloneToTemplate');

    $router->put('vms/{vmId}/power-on', 'VirtualMachineController@powerOn');
    $router->put('vms/{vmId}/power-off', 'VirtualMachineController@powerOff');
    $router->put('vms/{vmId}/power-shutdown', 'VirtualMachineController@shutdown');
    $router->put('vms/{vmId}/power-restart', 'VirtualMachineController@restart');
    $router->put('vms/{vmId}/power-reset', 'VirtualMachineController@reset');
    $router->put('vms/{vmId}/power-suspend', 'VirtualMachineController@suspend');

    $router->get('vms/{vmId}/tags', 'TagController@indexVMTags');
    $router->post('vms/{vmId}/tags', 'TagController@createVMTag');
    $router->get('vms/{vmId}/tags/{key}', 'TagController@showVMTag');
    $router->patch('vms/{vmId}/tags/{key}', 'TagController@updateVMTag');
    $router->delete('vms/{vmId}/tags/{key}', 'TagController@destroyVMTag');

    $router->post('vms/{vmId}/encrypt', 'VirtualMachineController@encrypt');
    $router->post('vms/{vmId}/decrypt', 'VirtualMachineController@decrypt');

    $router->post('vms/{vmId}/join-ad-domain', 'VirtualMachineController@joinActiveDirectoryDomain');
    $router->put('vms/{vmId}/console-session', 'VirtualMachineController@consoleSession');

    // Solution's
    $router->get('solutions', 'SolutionController@index');
    $router->get('solutions/{solutionId}', 'SolutionController@show');
    $router->patch('solutions/{solutionId}', 'SolutionController@update');
    $router->get('solutions/{solutionId}/vms', 'VirtualMachineController@getSolutionVMs');
    $router->get('solutions/{solutionId}/hosts', 'HostController@indexSolution');
    $router->get('solutions/{solutionId}/datastores', 'DatastoreController@indexSolution');
    $router->get('solutions/{solutionId}/datastores/default', 'DatastoreController@getSolutionDefault');
    $router->get('solutions/{solutionId}/sites', 'SolutionSiteController@getSolutionSites');
    $router->get('solutions/{solutionId}/networks', 'SolutionNetworkController@getSolutionNetworks');
    $router->get('solutions/{solutionId}/firewalls', 'FirewallController@getSolutionFirewalls');
    $router->get('solutions/{solutionId}/templates', 'TemplateController@indexSolutionTemplate');
    $router->get('solutions/{solutionId}/templates/{templateName}', 'TemplateController@showSolutionTemplate');
    $router->post('solutions/{solutionId}/templates/{templateName}/move',
        'TemplateController@renameSolutionTemplate');
    $router->delete('solutions/{solutionId}/templates/{templateName}', 'TemplateController@deleteSolutionTemplate');

    $router->get('solutions/{solutionId}/tags', 'TagController@indexSolutionTags');
    $router->post('solutions/{solutionId}/tags', 'TagController@createSolutionTag');
    $router->get('solutions/{solutionId}/tags/{tagKey}', 'TagController@showSolutionTag');
    $router->patch('solutions/{solutionId}/tags/{tagKey}', 'TagController@updateSolutionTag');
    $router->delete('solutions/{solutionId}/tags/{tagKey}', 'TagController@destroySolutionTag');

    // Solution Sites
    $router->get('sites', 'SolutionSiteController@index');
    $router->get('sites/{siteId}', 'SolutionSiteController@show');

    // Hosts
    $router->get('hosts', 'HostController@index');
    $router->get('hosts/{hostId}', 'HostController@show');

    // Hosts - Admin
    $router->group([
        'middleware' => [
            'is-admin',
        ],
    ], function () use ($router) {
        $router->get(
            'hosts/{hostId}/hardware',
            'HostController@hardware'
        );
        $router->post(
            'hosts/{hostId}/create',
            'HostController@createHost'
        );
        $router->delete(
            'hosts/{hostId}',
            'HostController@delete'
        );
        $router->post(
            'hosts/{hostId}/delete',
            'HostController@deleteHost'
        );
        $router->post(
            'hosts/{hostId}/rescan',
            'HostController@clusterRescan'
        );
    });

    // Datastores
    $router->get('datastores', 'DatastoreController@index');
    $router->get('datastores/{datastoreId}', 'DatastoreController@show');

    // Firewalls
    $router->get('firewalls', 'FirewallController@index');
    $router->get('firewalls/{firewallId}', 'FirewallController@show');
    $router->get('firewalls/{firewallId}/config', 'FirewallController@getFirewallConfig');

    // Pods
    $router->get('pods', 'PodController@index');
    $router->get('pods/{podId}', 'PodController@show');
    $router->get('pods/{podId}/templates', 'TemplateController@indexPodTemplate');
    $router->get('pods/{podId}/templates/{templateName}', 'TemplateController@showPodTemplate');
    $router->post('pods/{podId}/templates/{templateName}/move', 'TemplateController@renamePodTemplate');
    $router->get('pods/{podId}/gpu-profiles', 'PodController@gpuProfiles');
    $router->get('pods/{podId}/storage', 'PodController@indexStorage');

    $router->get('pods/{podId}/appliances', 'ApplianceController@podAvailability');
    $router->post('pods/{podId}/appliances', 'ApplianceController@addToPod');
    $router->delete('pods/{podId}/appliances/{applianceId}', 'ApplianceController@removeFromPod');
    $router->delete('pods/{podId}/templates/{templateName}', 'TemplateController@deletePodTemplate');
    $router->get('pods/{podId}/console-available', 'PodController@consoleAvailable');

    // Pod resource - Admin
    $router->group([
        'middleware' => [
            'is-admin',
        ],
    ], function () use ($router) {
        $router->get('pods/{podId}/resources', 'PodController@resource');
        $router->get('pods/{podId}/resources/types', 'PodController@resourceTypes');
        $router->post('pods/{podId}/resources', 'PodController@resourceAdd');
        $router->delete('pods/{podId}/resources/{resourceId}', 'PodController@resourceRemove');
        $router->put('pods/{podId}/resources/{resourceId}', 'PodController@resourceUpdate');
    });

    // Appliances
    $router->get('appliances', 'ApplianceController@index');
    $router->get('appliances/{applianceId}', 'ApplianceController@show');
    $router->get('appliances/{applianceId}/versions', 'ApplianceController@versions');
    $router->get('appliances/{applianceId}/version', 'ApplianceController@latestVersion');
    $router->get('appliances/{applianceId}/parameters', 'ApplianceController@latestVersionParameters');
    $router->get('appliances/{applianceId}/data', 'ApplianceController@latestVersionData');
    $router->get('appliances/{applianceId}/pods', 'ApplianceController@pods');
    $router->post('appliances', 'ApplianceController@create');
    $router->patch('appliances/{applianceId}', 'ApplianceController@update');
    $router->delete('appliances/{applianceId}', 'ApplianceController@delete');

    //Appliance Versions
    $router->get('appliance-versions', 'ApplianceVersionController@index');
    $router->get('appliance-versions/{applianceVersionId}', 'ApplianceVersionController@show');
    $router->post('appliance-versions', 'ApplianceVersionController@create');
    $router->patch('appliance-versions/{applianceVersionId}', 'ApplianceVersionController@update');
    $router->get('appliance-versions/{applianceVersionId}/parameters',
        'ApplianceVersionController@versionParameters');
    $router->delete('appliance-versions/{applianceVersionId}', 'ApplianceVersionController@delete');

    // Appliance Versions Data - Admin
    $router->group([
        'middleware' => [
            'is-admin',
            \App\Http\Middleware\Appliance\Version::class,
        ],
    ], function () use ($router) {
        $router->get(
            'appliance-versions/{appliance_version_uuid}/data',
            'Appliance\Version\DataController@index'
        );
        $router->get(
            'appliance-versions/{appliance_version_uuid}/data/{key}',
            'Appliance\Version\DataController@show'
        );
        $router->post(
            'appliance-versions/{appliance_version_uuid}/data',
            'Appliance\Version\DataController@create'
        );
        $router->patch(
            'appliance-versions/{appliance_version_uuid}/data/{key}',
            'Appliance\Version\DataController@update'
        );
        $router->delete(
            'appliance-versions/{appliance_version_uuid}/data/{key}',
            'Appliance\Version\DataController@delete'
        );
    });

    //Appliance Parameters
    $router->get('appliance-parameters', 'ApplianceParametersController@index');
    $router->get('appliance-parameters/{parameterId}', 'ApplianceParametersController@show');
    $router->post('appliance-parameters', 'ApplianceParametersController@create');
    $router->patch('appliance-parameters/{parameterId}', 'ApplianceParametersController@update');
    $router->delete('appliance-parameters/{parameterId}', 'ApplianceParametersController@delete');

    //GPU Profiles
    $router->get('gpu-profiles', 'GpuProfileController@index');
    $router->get('gpu-profiles/{profileId}', 'GpuProfileController@show');

    // Active Directory Domains
    $router->get('active-directory/domains', 'ActiveDirectoryDomainController@index');
    $router->get('active-directory/domains/{domainId}', 'ActiveDirectoryDomainController@show');

    // IOPS
    $router->get('iops', 'IOPSController@index');
    $router->get('iops/{id}', 'IOPSController@show');

    /**
     * Base middleware + reseller ID scope
     */
    $router->group(['middleware' => 'has-reseller-id'], function () use ($router) {
        //Credits
        $router->get('credits', 'CreditsController@index');


        /**
         * Base middleware + reseller ID scope + is-administrator
         */
        $router->group(['middleware' => 'is-admin'], function () use ($router) {

        });
    });


    /**
     * Base middleware + is-administrator
     */

    $router->group(['middleware' => 'is-admin'], function () use ($router) {
        // Datastores
        $router->post('datastores/{datastoreId}/expand', 'DatastoreController@expand');
        $router->post('datastores', 'DatastoreController@create');

        $router->patch('datastores/{datastoreId}', 'DatastoreController@update');
        $router->delete('datastores/{datastoreId}', 'DatastoreController@delete');
        $router->post('datastores/{datastoreId}/expandvolume', 'DatastoreController@expandVolume');
        $router->post('datastores/{datastoreId}/rescan', 'DatastoreController@clusterRescan');
        $router->post('datastores/{datastoreId}/expanddatastore', 'DatastoreController@expandDatastore');
        $router->post('datastores/{datastoreId}/iops', 'DatastoreController@updateIops');

        $router->post('datastores/{datastoreId}/createvolume', 'DatastoreController@createvolume');
        $router->post('datastores/{datastoreId}/create', 'DatastoreController@createDatastore');

        // Storage volume sets
        $router->get('volumesets', 'VolumeSetController@index');
        $router->get('volumesets/{volumeSetId}', 'VolumeSetController@show');
        $router->post('volumesets', 'VolumeSetController@create');
        $router->post('volumesets/{volumeSetId}/iops', 'VolumeSetController@setIOPS');
        $router->post('volumesets/{volumeSetId}/export', 'VolumeSetController@export');
        $router->post('volumesets/{volumeSetId}/datastores', 'VolumeSetController@addDatastore');
        $router->delete('volumesets/{volumeSetId}/datastores/{datastore_id}', 'VolumeSetController@removeDatastore');

        $router->delete('volumesets/{volumeSetId}', 'VolumeSetController@delete');
        $router->post('volumesets/{volumeSetId}/delete', 'VolumeSetController@deleteVolumeSet');
        $router->get('volumesets/{volumeSetId}/volumes', 'VolumeSetController@volumes');


        // Storage host sets
        $router->get('hostsets', 'HostSetController@index');
        $router->get('hostsets/{hostSetId}', 'HostSetController@show');
        $router->post('hostsets', 'HostSetController@create');
        $router->post('hostsets/{hostSetId}/hosts', 'HostSetController@addHost');
        $router->delete('hostsets/{hostSetId}/hosts/{hostId}', 'HostSetController@removeHost');

        //DRS
        $router->get('solutions/{solutionId}/constraints', 'SolutionController@getDrsRules');
    });
});

// Public Support
$router->group($baseRouteParameters, function () use ($router) {
    $router->group(['middleware' => 'is-admin'], function () use ($router) {
        $router->get('support', 'PublicSupportController@index');
        $router->post('support', 'PublicSupportController@store');
        $router->get('support/{id}', [
            'uses' => 'PublicSupportController@show',
            'as' => 'support.item',
        ]);
    });
});
