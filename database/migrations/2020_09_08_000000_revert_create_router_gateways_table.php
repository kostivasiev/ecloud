<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RevertCreateRouterGatewaysTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->rename('gateway_router', 'router_gateways');
        Schema::connection('ecloud')->table('router_gateways', function (Blueprint $table) {
            $table->renameColumn('gateway_id', 'gateways_id');
        });
        (new CreateRouterGatewaysTable())->down();
    }

    public function down()
    {
        (new CreateRouterGatewaysTable())->up();
        Schema::connection('ecloud')->rename('router_gateways', 'gateway_router');
        Schema::connection('ecloud')->table('gateway_router', function (Blueprint $table) {
            $table->renameColumn('gateways_id', 'gateway_id');
        });
    }
}
