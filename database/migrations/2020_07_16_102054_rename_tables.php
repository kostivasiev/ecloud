<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->rename('router', 'routers');
        Schema::connection('ecloud')->rename('dhcp', 'dhcps');
        Schema::connection('ecloud')->rename('availability_zones_router', 'availability_zone_router');
        Schema::connection('ecloud')->rename('router_gateways', 'gateway_router');
        Schema::connection('ecloud')->table('availability_zone_router', function (Blueprint $table) {
            $table->renameColumn('zone_id', 'availability_zone_id');
        });
        Schema::connection('ecloud')->table('gateway_router', function (Blueprint $table) {
            $table->renameColumn('gateways_id', 'gateway_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->rename('routers', 'router');
        Schema::connection('ecloud')->rename('dhcps', 'dhcp');
        Schema::connection('ecloud')->rename('availability_zone_router', 'availability_zones_router');
        Schema::connection('ecloud')->rename('gateway_router', 'router_gateways');
        Schema::connection('ecloud')->table('availability_zones_router', function (Blueprint $table) {
            $table->renameColumn('availability_zone_id', 'zone_id');
        });
        Schema::connection('ecloud')->table('router_gateways', function (Blueprint $table) {
            $table->renameColumn('gateway_id', 'gateways_id');
        });
    }
}
