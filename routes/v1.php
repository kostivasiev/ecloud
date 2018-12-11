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
    $router->delete('vms/{vm_id}', 'VirtualMachineController@destroy');

    // todo resize
    // todo create template
    $router->post('vms/{vmId}/clone', 'VirtualMachineController@clone');

    $router->put('vms/{vmId}/power-on', 'VirtualMachineController@powerOn');
    $router->put('vms/{vmId}/power-off', 'VirtualMachineController@powerOff');
    $router->put('vms/{vmId}/power-cycle', 'VirtualMachineController@powerCycle');

    // todo tags
});


// Templates
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('templates', 'TemplateController@index');

    $router->get('templates/{template_name}', 'TemplateController@show');
    $router->put('templates/{template_name}', 'TemplateController@renameTemplate');
    $router->delete('templates/{template_name}', 'TemplateController@deleteTemplate');

    $router->get('solutions/{solution_id}/templates', 'TemplateController@solutionTemplates');
});


// Solution's
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('solutions', 'SolutionController@index');

    $router->get('solutions/{solution_id}', 'SolutionController@show');
    $router->patch('solutions/{solution_id}', 'SolutionController@update');

    $router->get('solutions/{solution_id}/vms', 'VirtualMachineController@getSolutionVMs');

    $router->get('solutions/{solution_id}/networks', 'SolutionNetworkController@getSolutionNetworks');

    $router->get('solutions/{solution_id}/sites', 'SolutionSiteController@getSolutionSites');

    $router->get('solutions/{solution_id}/firewalls', 'FirewallController@getSolutionFirewalls');

    $router->get('solutions/{solution_id}/tags', 'TagController@showSolutionTags');
//    $router->post('solutions/{solution_id}/tags', 'TagController@createSolutionTags');
//    $router->put('solutions/{solution_id}/tags/{name}', 'TagController@saveSolutionTag');
//    $router->delete('solutions/{solution_id}/tags/{name}', 'TagController@destroySolutionTag');
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

    // todo templates
    // todo datastores
});

// todo prices
