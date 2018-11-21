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
    // Return a VM Collection
    $router->get('vms', 'VirtualMachineController@index');

    //Return a VM Resource
    $router->get('vms/{vm_id}', 'VirtualMachineController@show');

    //Power the VM On
    $router->put('vms/{vm_id}/power-on', 'VirtualMachineController@powerOn');

    //Power the VM Off
    $router->put('vms/{vm_id}/power-off', 'VirtualMachineController@powerOff');

    //Power-cycle the VM
    $router->put('vms/{vm_id}/power-cycle', 'VirtualMachineController@powerCycle');
});

// Templates
$templateRouteParameters = array_merge($baseRouteParameters, [
    'namespace' => 'V1',
    'prefix' => 'v1',
]);
$router->group($templateRouteParameters, function () use ($router) {
    // Return a VM Collection
    $router->get('templates', 'TemplateController@index');
});

// Solution's
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('solutions', 'SolutionController@index');
    $router->get('solutions/{solution_id}', 'SolutionController@show');

    // vlan's
    $router->get('solutions/{solution_id}/vlans', 'SolutionVlanController@getSolutionVlans');

    // sites
    $router->get('solutions/{solution_id}/sites', 'SolutionSiteController@getSolutionSites');

    // firewalls
    $router->get('solutions/{solution_id}/firewalls', 'FirewallController@getSolutionFirewalls');
});


// Solution Sites
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('sites', 'SolutionSiteController@index');
    $router->get('sites/{site_id}', 'SolutionSiteController@show');
});


// Firewalls
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('firewalls', 'FirewallController@index');
    $router->get('firewalls/{firewall_id}', 'FirewallController@show');

    // config
    $router->get('firewalls/{firewall_id}/config', 'FirewallController@getFirewallConfig');
});

// Pods
$router->group($baseRouteParameters, function () use ($router) {
    $router->get('pods', 'PodController@index');
    $router->get('pods/{pod_id}', 'PodController@show');
});
