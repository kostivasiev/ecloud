<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RevertCreateRouterGatewaysTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('gateway_router', function (Blueprint $table) {
            $table->renameColumn('gateway_id', 'gateways_id');
        });
        Schema::connection('ecloud')->rename('gateway_router', 'router_gateways');

        require_once(app()->basePath('database/migrations/2020_07_10_085908_create_router_gateways_table.php'));
        (new CreateRouterGatewaysTable())->down();
    }

    public function down()
    {
        require_once(app()->basePath('database/migrations/2020_07_10_085908_create_router_gateways_table.php'));
        (new CreateRouterGatewaysTable())->up();

        Schema::connection('ecloud')->table('router_gateways', function (Blueprint $table) {
            $table->renameColumn('gateways_id', 'gateway_id');
        });
        Schema::connection('ecloud')->rename('router_gateways', 'gateway_router');
    }
}
