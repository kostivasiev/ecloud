<?php

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
