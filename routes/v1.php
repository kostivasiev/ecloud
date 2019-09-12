<?php

/**
 * v1 Routes
 */

$middleware = [
    'auth',
    'paginator-limit:'.env('PAGINATION_LIMIT')
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


    // Solution's
    $router->get('solutions', 'SolutionController@index');
    $router->get('solutions/{solution_id}', 'SolutionController@show');
    $router->patch('solutions/{solution_id}', 'SolutionController@update');
    $router->get('solutions/{solution_id}/vms', 'VirtualMachineController@getSolutionVMs');
    $router->get('solutions/{solution_id}/hosts', 'HostController@indexSolution');
    $router->get('solutions/{solution_id}/datastores', 'DatastoreController@indexSolution');
    $router->get('solutions/{solution_id}/sites', 'SolutionSiteController@getSolutionSites');
    $router->get('solutions/{solution_id}/networks', 'SolutionNetworkController@getSolutionNetworks');
    $router->get('solutions/{solution_id}/firewalls', 'FirewallController@getSolutionFirewalls');
    $router->get('solutions/{solution_id}/templates', 'TemplateController@indexSolutionTemplate');
    $router->get('solutions/{solution_id}/templates/{template_name}', 'TemplateController@showSolutionTemplate');
    $router->post('solutions/{solution_id}/templates/{template_name}/move', 'TemplateController@renameSolutionTemplate');
    $router->delete('solutions/{solution_id}/templates/{template_name}', 'TemplateController@deleteSolutionTemplate');
    $router->get('solutions/{solution_id}/tags', 'TagController@indexSolutionTags');
    $router->post('solutions/{solution_id}/tags', 'TagController@createSolutionTag');
    $router->get('solutions/{solution_id}/tags/{name}', 'TagController@showSolutionTag');
    $router->patch('solutions/{solution_id}/tags/{name}', 'TagController@updateSolutionTag');
    $router->delete('solutions/{solution_id}/tags/{name}', 'TagController@destroySolutionTag');


    // Solution Sites
    $router->get('sites', 'SolutionSiteController@index');
    $router->get('sites/{site_id}', 'SolutionSiteController@show');


    // Hosts
    $router->get('hosts', 'HostController@index');
    $router->get('hosts/{host_id}', 'HostController@show');


    // Datastores
    $router->get('datastores', 'DatastoreController@index');
    $router->get('datastores/{datastore_id}', 'DatastoreController@show');

    // IOPS
    $router->get('iops', 'IOPSController@index');


    // Firewalls
    $router->get('firewalls', 'FirewallController@index');
    $router->get('firewalls/{firewall_id}', 'FirewallController@show');
    $router->get('firewalls/{firewall_id}/config', 'FirewallController@getFirewallConfig');


    // Pods
    $router->get('pods', 'PodController@index');
    $router->get('pods/{pod_id}', 'PodController@show');
    $router->get('pods/{pod_id}/templates', 'TemplateController@indexPodTemplate');
    $router->get('pods/{pod_id}/templates/{template_name}', 'TemplateController@showPodTemplate');
    $router->post('pods/{pod_id}/templates/{template_name}/move', 'TemplateController@renamePodTemplate');
    $router->get('pods/{pod_id}/gpu-profiles', 'PodController@gpuProfiles');
    // todo datastores

    $router->get('pods/{pod_id}/appliances', 'ApplianceController@podAvailability');
    $router->post('pods/{pod_id}/appliances', 'ApplianceController@addToPod');
    $router->delete('pods/{pod_id}/appliances/{appliance_id}', 'ApplianceController@removeFromPod');
    $router->delete('pods/{pod_id}/templates/{template_name}', 'TemplateController@deletePodTemplate');


    // Appliances
    $router->get('appliances', 'ApplianceController@index');
    $router->get('appliances/{appliance_id}', 'ApplianceController@show');
    $router->get('appliances/{appliance_id}/versions', 'ApplianceController@versions');
    $router->get('appliances/{appliance_id}/version', 'ApplianceController@latestVersion');
    $router->get('appliances/{appliance_id}/parameters', 'ApplianceController@latestVersionParameters');
    $router->get('appliances/{appliance_id}/pods', 'ApplianceController@pods');
    $router->post('appliances', 'ApplianceController@create');
    $router->patch('appliances/{appliance_id}', 'ApplianceController@update');
    $router->delete('appliances/{appliance_id}', 'ApplianceController@delete');


    //Appliance Versions
    $router->get('appliance-versions', 'ApplianceVersionController@index');
    $router->get('appliance-versions/{appliance_version_id}', 'ApplianceVersionController@show');
    $router->post('appliance-versions', 'ApplianceVersionController@create');
    $router->patch('appliance-versions/{appliance_version_uuid}', 'ApplianceVersionController@update');
    $router->get('appliance-versions/{appliance_version_uuid}/parameters', 'ApplianceVersionController@versionParameters');
    $router->delete('appliance-versions/{appliance_version_uuid}', 'ApplianceVersionController@delete');


    //Appliance Parameters
    $router->get('appliance-parameters', 'ApplianceParametersController@index');
    $router->get('appliance-parameters/{parameter_uuid}', 'ApplianceParametersController@show');
    $router->post('appliance-parameters', 'ApplianceParametersController@create');
    $router->patch('appliance-parameters/{parameter_uuid}', 'ApplianceParametersController@update');
    $router->delete('appliance-parameters/{parameter_uuid}', 'ApplianceParametersController@delete');

    //GPU Profiles
    $router->get('gpu-profiles', 'GpuProfileController@index');
    $router->get('gpu-profiles/{profile_id}', 'GpuProfileController@show');

    // Active Directory Domains
    $router->get('active-directory/domains', 'ActiveDirectoryDomainController@index');
    $router->get('active-directory/domains/{domain_id}', 'ActiveDirectoryDomainController@show');

    // IOPS
    $router->get('iops', 'IOPSController@index');
    $router->get('iops/{uuid}', 'IOPSController@show');

    /**
     * Base middleware + reseller ID scope
     */
    $router->group(['middleware' => 'has-reseller-id'], function () use ($router) {
        //Credits
        $router->get('credits', 'CreditsController@index');


        /**
         * Base middleware + reseller ID scope + is-administrator
         */
        $router->group(['middleware' => 'is-administrator'], function () use ($router) {

        });
    });


    /**
     * Base middleware + is-administrator
     */

    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        // Datastores
        $router->post('datastores/{datastore_id}/expand', 'DatastoreController@expand'); //Fires off automation
        $router->post('datastores', 'DatastoreController@create'); //Fires off automation

        $router->patch('datastores/{datastore_id}', 'DatastoreController@update');
        $router->post('datastores/{datastore_id}/expandvolume', 'DatastoreController@expandVolume'); //Expand the datastore volume via Artisan
        $router->post('datastores/{datastore_id}/rescan', 'DatastoreController@clusterRescan'); //Perform cluster rescan
        $router->post('datastores/{datastore_id}/expanddatastore', 'DatastoreController@expandDatastore'); //Expand the datastore on VMWare

        $router->post('datastores/{datastore_id}/createvolume', 'DatastoreController@createvolume'); // Create a new volume on the SAN
        $router->post('datastores/{datastore_id}/create', 'DatastoreController@createDatastore'); // Create a new volume on the VMWare

        // Storage volume sets
        $router->get('volumesets', 'VolumeSetController@index');
        $router->get('volumesets/{volue_set_id}', 'VolumeSetController@show');
        $router->post('volumesets', 'VolumeSetController@create'); //Create a volume set
        $router->post('volumesets/{volume_set_id}/iops', 'VolumeSetController@setIOPS');
        $router->post('volumesets/{volume_set_id}/export', 'VolumeSetController@export'); // Export volume set to host set

        // Storage host sets
        $router->post('hostsets', 'HostSetController@create'); //Create a host set

        // Hosts
        //Create a host on the SAN, lets use /create and reserve POST /hosts for a customer facing create host endpoint later
        $router->post('hosts/{host_id}/create', 'HostController@createHost');

        $router->post('volumesets/{volume_set_id}/datastores', 'VolumeSetController@addDatastore'); // Add datastore/volume to a volume set
    });
});


