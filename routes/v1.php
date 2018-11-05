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
    'middleware' => $middleware
];


// VM's
$hostRouteParameters = $baseRouteParameters;
$hostRouteParameters['namespace'] = 'V1';
$router->group($hostRouteParameters, function () use ($router) {

    /**
     * GET /vms
     * Return a VM Collection
     */
    $router->get('vms', 'VirtualMachineController@index');

    /**
     * GET vms/{vm_id}
     * Return a VM Resource
     */
    $router->get('vms/{vm_id}', 'VirtualMachineController@show');
});


// Hybrid/Private Solution's
$solutionRouteParameters = array_merge($baseRouteParameters, array(
    'namespace' => 'V1',
    'prefix' => 'v1',
));
$router->group($solutionRouteParameters, function () use ($router) {
    // solutions
    $router->get('solutions', 'SolutionController@index');
    $router->get('solutions/{solution_id}', 'SolutionController@show');

    // solution vlan's
    $router->get('solutions/{solution_id}/vlans', 'VlanController@getSolutionVlans');
});
