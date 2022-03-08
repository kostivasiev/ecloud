<?php

/**
 * v1 Routes
 */

/**
 * Grouping for routes based on middleware allocation
 */
Route::group([
    'prefix' => 'v1',
    'namespace' => 'App\Http\Controllers\V1',
    'middleware' =>  [
        'auth',
        'paginator-limit:' . env('PAGINATION_LIMIT')
    ]
], function () {
    /**
     * Base middleware only
     *
     * Add routes to this section that require base middleware only
     */

    // Virtual Machines's
    Route::get('vms', 'VirtualMachineController@index');
    Route::post('vms', 'VirtualMachineController@create');
    Route::get('vms/{vmId}', 'VirtualMachineController@show');
    /**
     * @deprecated
     * We are replacing the PUT /vms/{vmId} endpoint with PATCH as it's a partial resource update.
     * The PUT endpoint is to be removed once we are happy nobody is using it. Have added some logging to monitor usage.
     */
    Route::put('vms/{vmId}', 'VirtualMachineController@update');
    Route::patch('vms/{vmId}', 'VirtualMachineController@update');
    Route::delete('vms/{vmId}', 'VirtualMachineController@destroy');

    Route::post('vms/{vmId}/clone', 'VirtualMachineController@clone');
    Route::post('vms/{vmId}/clone-to-template', 'VirtualMachineController@cloneToTemplate');

    Route::put('vms/{vmId}/power-on', 'VirtualMachineController@powerOn');
    Route::put('vms/{vmId}/power-off', 'VirtualMachineController@powerOff');
    Route::put('vms/{vmId}/power-shutdown', 'VirtualMachineController@shutdown');
    Route::put('vms/{vmId}/power-restart', 'VirtualMachineController@restart');
    Route::put('vms/{vmId}/power-reset', 'VirtualMachineController@reset');
    Route::put('vms/{vmId}/power-suspend', 'VirtualMachineController@suspend');

    Route::get('vms/{vmId}/tags', 'TagController@indexVMTags');
    Route::post('vms/{vmId}/tags', 'TagController@createVMTag');
    Route::get('vms/{vmId}/tags/{key}', 'TagController@showVMTag');
    Route::patch('vms/{vmId}/tags/{key}', 'TagController@updateVMTag');
    Route::delete('vms/{vmId}/tags/{key}', 'TagController@destroyVMTag');

    Route::post('vms/{vmId}/encrypt', 'VirtualMachineController@encrypt');
    Route::post('vms/{vmId}/decrypt', 'VirtualMachineController@decrypt');

    Route::post('vms/{vmId}/join-ad-domain', 'VirtualMachineController@joinActiveDirectoryDomain');
    Route::put('vms/{vmId}/console-session', 'VirtualMachineController@consoleSession');

    // Solution's
    Route::get('solutions', 'SolutionController@index');
    Route::get('solutions/{solutionId}', 'SolutionController@show');
    Route::patch('solutions/{solutionId}', 'SolutionController@update');
    Route::get('solutions/{solutionId}/vms', 'VirtualMachineController@getSolutionVMs');
    Route::get('solutions/{solutionId}/hosts', 'HostController@indexSolution');
    Route::get('solutions/{solutionId}/datastores', 'DatastoreController@indexSolution');
    Route::get('solutions/{solutionId}/datastores/default', 'DatastoreController@getSolutionDefault');
    Route::get('solutions/{solutionId}/sites', 'SolutionSiteController@getSolutionSites');
    Route::get('solutions/{solutionId}/networks', 'SolutionNetworkController@getSolutionNetworks');
    Route::get('solutions/{solutionId}/firewalls', 'FirewallController@getSolutionFirewalls');
    Route::get('solutions/{solutionId}/templates', 'TemplateController@indexSolutionTemplate');
    Route::get('solutions/{solutionId}/templates/{templateName}', 'TemplateController@showSolutionTemplate');
    Route::post('solutions/{solutionId}/templates/{templateName}/move',
        'TemplateController@renameSolutionTemplate');
    Route::delete('solutions/{solutionId}/templates/{templateName}', 'TemplateController@deleteSolutionTemplate');

    Route::get('solutions/{solutionId}/tags', 'TagController@indexSolutionTags');
    Route::post('solutions/{solutionId}/tags', 'TagController@createSolutionTag');
    Route::get('solutions/{solutionId}/tags/{tagKey}', 'TagController@showSolutionTag');
    Route::patch('solutions/{solutionId}/tags/{tagKey}', 'TagController@updateSolutionTag');
    Route::delete('solutions/{solutionId}/tags/{tagKey}', 'TagController@destroySolutionTag');

    // Solution Sites
    Route::get('sites', 'SolutionSiteController@index');
    Route::get('sites/{siteId}', 'SolutionSiteController@show');

    // Hosts
    Route::get('hosts', 'HostController@index');
    Route::get('hosts/{hostId}', 'HostController@show');

    // Hosts - Admin
    Route::group([
        'middleware' => [
            'is-admin',
        ],
    ], function () {
        Route::get(
            'hosts/{hostId}/hardware',
            'HostController@hardware'
        );
        Route::post(
            'hosts/{hostId}/create',
            'HostController@createHost'
        );
        Route::delete(
            'hosts/{hostId}',
            'HostController@delete'
        );
        Route::post(
            'hosts/{hostId}/delete',
            'HostController@deleteHost'
        );
        Route::post(
            'hosts/{hostId}/rescan',
            'HostController@clusterRescan'
        );
    });

    // Datastores
    Route::get('datastores', 'DatastoreController@index');
    Route::get('datastores/{datastoreId}', 'DatastoreController@show');

    // Firewalls
    Route::get('firewalls', 'FirewallController@index');
    Route::get('firewalls/{firewallId}', 'FirewallController@show');
    Route::get('firewalls/{firewallId}/config', 'FirewallController@getFirewallConfig');

    // Pods
    Route::get('pods', 'PodController@index');
    Route::get('pods/{podId}', 'PodController@show');
    Route::get('pods/{podId}/templates', 'TemplateController@indexPodTemplate');
    Route::get('pods/{podId}/templates/{templateName}', 'TemplateController@showPodTemplate');
    Route::post('pods/{podId}/templates/{templateName}/move', 'TemplateController@renamePodTemplate');
    Route::get('pods/{podId}/gpu-profiles', 'PodController@gpuProfiles');
    Route::get('pods/{podId}/storage', 'PodController@indexStorage');

    Route::get('pods/{podId}/appliances', 'ApplianceController@podAvailability');
    Route::post('pods/{podId}/appliances', 'ApplianceController@addToPod');
    Route::delete('pods/{podId}/appliances/{applianceId}', 'ApplianceController@removeFromPod');
    Route::delete('pods/{podId}/templates/{templateName}', 'TemplateController@deletePodTemplate');
    Route::get('pods/{podId}/console-available', 'PodController@consoleAvailable');

    // Pod resource - Admin
    Route::group([
        'middleware' => [
            'is-admin',
        ],
    ], function () {
        Route::get('pods/{podId}/resources', 'PodController@resource');
        Route::get('pods/{podId}/resources/types', 'PodController@resourceTypes');
        Route::post('pods/{podId}/resources', 'PodController@resourceAdd');
        Route::delete('pods/{podId}/resources/{resourceId}', 'PodController@resourceRemove');
        Route::put('pods/{podId}/resources/{resourceId}', 'PodController@resourceUpdate');
    });

    // Appliances
    Route::get('appliances', 'ApplianceController@index');
    Route::get('appliances/{applianceId}', 'ApplianceController@show');
    Route::get('appliances/{applianceId}/versions', 'ApplianceController@versions');
    Route::get('appliances/{applianceId}/version', 'ApplianceController@latestVersion');
    Route::get('appliances/{applianceId}/parameters', 'ApplianceController@latestVersionParameters');
    Route::get('appliances/{applianceId}/data', 'ApplianceController@latestVersionData');
    Route::get('appliances/{applianceId}/pods', 'ApplianceController@pods');
    Route::post('appliances', 'ApplianceController@create');
    Route::patch('appliances/{applianceId}', 'ApplianceController@update');
    Route::delete('appliances/{applianceId}', 'ApplianceController@delete');

    //Appliance Versions
    Route::get('appliance-versions', 'ApplianceVersionController@index');
    Route::get('appliance-versions/{applianceVersionId}', 'ApplianceVersionController@show');
    Route::post('appliance-versions', 'ApplianceVersionController@create');
    Route::patch('appliance-versions/{applianceVersionId}', 'ApplianceVersionController@update');
    Route::get('appliance-versions/{applianceVersionId}/parameters',
        'ApplianceVersionController@versionParameters');
    Route::delete('appliance-versions/{applianceVersionId}', 'ApplianceVersionController@delete');

    // Appliance Versions Data - Admin
    Route::group([
        'middleware' => [
            'is-admin',
            \App\Http\Middleware\Appliance\Version::class,
        ],
    ], function () {
        Route::get(
            'appliance-versions/{appliance_version_uuid}/data',
            'Appliance\Version\DataController@index'
        );
        Route::get(
            'appliance-versions/{appliance_version_uuid}/data/{key}',
            'Appliance\Version\DataController@show'
        );
        Route::post(
            'appliance-versions/{appliance_version_uuid}/data',
            'Appliance\Version\DataController@create'
        );
        Route::patch(
            'appliance-versions/{appliance_version_uuid}/data/{key}',
            'Appliance\Version\DataController@update'
        );
        Route::delete(
            'appliance-versions/{appliance_version_uuid}/data/{key}',
            'Appliance\Version\DataController@delete'
        );
    });

    //Appliance Parameters
    Route::get('appliance-parameters', 'ApplianceParametersController@index');
    Route::get('appliance-parameters/{parameterId}', 'ApplianceParametersController@show');
    Route::post('appliance-parameters', 'ApplianceParametersController@create');
    Route::patch('appliance-parameters/{parameterId}', 'ApplianceParametersController@update');
    Route::delete('appliance-parameters/{parameterId}', 'ApplianceParametersController@delete');

    //GPU Profiles
    Route::get('gpu-profiles', 'GpuProfileController@index');
    Route::get('gpu-profiles/{profileId}', 'GpuProfileController@show');

    // Active Directory Domains
    Route::get('active-directory/domains', 'ActiveDirectoryDomainController@index');
    Route::get('active-directory/domains/{domainId}', 'ActiveDirectoryDomainController@show');

    // IOPS
    Route::get('iops', 'IOPSController@index');
    Route::get('iops/{id}', 'IOPSController@show');

    Route::middleware('has-reseller-id')->get('credits', 'CreditsController@index');

    /**
     * Base middleware + is-administrator
     */
    Route::group(['middleware' => 'is-admin'], function () {
        // Datastores
        Route::post('datastores/{datastoreId}/expand', 'DatastoreController@expand');
        Route::post('datastores', 'DatastoreController@create');

        Route::patch('datastores/{datastoreId}', 'DatastoreController@update');
        Route::delete('datastores/{datastoreId}', 'DatastoreController@delete');
        Route::post('datastores/{datastoreId}/expandvolume', 'DatastoreController@expandVolume');
        Route::post('datastores/{datastoreId}/rescan', 'DatastoreController@clusterRescan');
        Route::post('datastores/{datastoreId}/expanddatastore', 'DatastoreController@expandDatastore');
        Route::post('datastores/{datastoreId}/iops', 'DatastoreController@updateIops');

        Route::post('datastores/{datastoreId}/createvolume', 'DatastoreController@createvolume');
        Route::post('datastores/{datastoreId}/create', 'DatastoreController@createDatastore');

        // Storage volume sets
        Route::get('volumesets', 'VolumeSetController@index');
        Route::get('volumesets/{volumeSetId}', 'VolumeSetController@show');
        Route::post('volumesets', 'VolumeSetController@create');
        Route::post('volumesets/{volumeSetId}/iops', 'VolumeSetController@setIOPS');
        Route::post('volumesets/{volumeSetId}/export', 'VolumeSetController@export');
        Route::post('volumesets/{volumeSetId}/datastores', 'VolumeSetController@addDatastore');
        Route::delete('volumesets/{volumeSetId}/datastores/{datastore_id}', 'VolumeSetController@removeDatastore');

        Route::delete('volumesets/{volumeSetId}', 'VolumeSetController@delete');
        Route::post('volumesets/{volumeSetId}/delete', 'VolumeSetController@deleteVolumeSet');
        Route::get('volumesets/{volumeSetId}/volumes', 'VolumeSetController@volumes');


        // Storage host sets
        Route::get('hostsets', 'HostSetController@index');
        Route::get('hostsets/{hostSetId}', 'HostSetController@show');
        Route::post('hostsets', 'HostSetController@create');
        Route::post('hostsets/{hostSetId}/hosts', 'HostSetController@addHost');
        Route::delete('hostsets/{hostSetId}/hosts/{hostId}', 'HostSetController@removeHost');

        //DRS
        Route::get('solutions/{solutionId}/constraints', 'SolutionController@getDrsRules');
    });

    Route::group(['middleware' => 'is-admin'], function () {
        Route::get('support', 'PublicSupportController@index');
        Route::post('support', 'PublicSupportController@store');
        Route::get('support/{id}', [
            'uses' => 'PublicSupportController@show',
            'as' => 'support.item',
        ]);
    });
});
