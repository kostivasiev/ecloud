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


// Virtual Machines's
$router->group($baseRouteParameters, function () use ($router) {
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
    // todo create template
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
});


// Templates
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('templates', 'TemplateController@index');

    $router->get('templates/{template_name}', 'TemplateController@show');
    $router->delete('templates/{template_name}', 'TemplateController@deleteTemplate');

    $router->post('templates/{template_name}/move', 'TemplateController@renameTemplate');
});



// Solution's
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('solutions', 'SolutionController@index');

    $router->get('solutions/{solution_id}', 'SolutionController@show');
    $router->patch('solutions/{solution_id}', 'SolutionController@update');

    $router->get('solutions/{solution_id}/vms', 'VirtualMachineController@getSolutionVMs');
    $router->get('solutions/{solution_id}/hosts', 'HostController@indexSolution');
    $router->get('solutions/{solution_id}/datastores', 'DatastoreController@indexSolution');
    $router->get('solutions/{solution_id}/templates', 'TemplateController@solutionTemplates');
    $router->get('solutions/{solution_id}/sites', 'SolutionSiteController@getSolutionSites');
    $router->get('solutions/{solution_id}/networks', 'SolutionNetworkController@getSolutionNetworks');
    $router->get('solutions/{solution_id}/firewalls', 'FirewallController@getSolutionFirewalls');

    $router->get('solutions/{solution_id}/tags', 'TagController@indexSolutionTags');
    $router->post('solutions/{solution_id}/tags', 'TagController@createSolutionTag');
    $router->get('solutions/{solution_id}/tags/{name}', 'TagController@showSolutionTag');
    $router->patch('solutions/{solution_id}/tags/{name}', 'TagController@updateSolutionTag');
    $router->delete('solutions/{solution_id}/tags/{name}', 'TagController@destroySolutionTag');
});


// Solution Sites
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('sites', 'SolutionSiteController@index');

    $router->get('sites/{site_id}', 'SolutionSiteController@show');
});


// Hosts
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('hosts', 'HostController@index');

    $router->get('hosts/{host_id}', 'HostController@show');
});


// Datastores
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('datastores', 'DatastoreController@index');

    $router->get('datastores/{datastore_id}', 'DatastoreController@show');
});


// Firewalls
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('firewalls', 'FirewallController@index');

    $router->get('firewalls/{firewall_id}', 'FirewallController@show');

    $router->get('firewalls/{firewall_id}/config', 'FirewallController@getFirewallConfig');
});


// Pods
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('pods', 'PodController@index');

    $router->get('pods/{pod_id}', 'PodController@show');

    $router->get('pods/{pod_id}/templates', 'TemplateController@podTemplates');
    // todo datastores

    $router->get('pods/{pod_id}/appliances', 'ApplianceController@podAvailability');
    $router->post('pods/{pod_id}/appliances', 'ApplianceController@addToPod');
    $router->delete('pods/{pod_id}/appliances/{appliance_id}', 'ApplianceController@removeFromPod');
});


// Appliances
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('appliances', 'ApplianceController@index');
    $router->get('appliances/{appliance_id}', 'ApplianceController@show');
    $router->get('appliances/{appliance_id}/versions', 'ApplianceController@versions');
    $router->get('appliances/{appliance_id}/version', 'ApplianceController@latestVersion');
    $router->get('appliances/{appliance_id}/parameters', 'ApplianceController@latestVersionParameters');

    $router->post('appliances', 'ApplianceController@create');
    $router->patch('appliances/{appliance_id}', 'ApplianceController@update');
    $router->delete('appliances/{appliance_id}', 'ApplianceController@delete');
});

//Appliance Versions
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('appliance-versions', 'ApplianceVersionController@index');
    $router->get('appliance-versions/{appliance_version_id}', 'ApplianceVersionController@show');
    $router->post('appliance-versions', 'ApplianceVersionController@create');
    $router->patch('appliance-versions/{appliance_version_uuid}', 'ApplianceVersionController@update');
    $router->get('appliance-versions/{appliance_version_uuid}/parameters', 'ApplianceVersionController@versionParameters');
    $router->delete('appliance-versions/{appliance_version_uuid}', 'ApplianceVersionController@delete');
});

//Appliance Parameters
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('appliance-parameters', 'ApplianceParametersController@index');
    $router->get('appliance-parameters/{parameter_uuid}', 'ApplianceParametersController@show');
    $router->post('appliance-parameters', 'ApplianceParametersController@create');
    $router->patch('appliance-parameters/{parameter_uuid}', 'ApplianceParametersController@update');
    $router->delete('appliance-parameters/{parameter_uuid}', 'ApplianceParametersController@delete');
});
