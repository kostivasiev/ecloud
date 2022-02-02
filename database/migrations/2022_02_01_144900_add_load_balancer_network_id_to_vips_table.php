<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLoadBalancerNetworkIdToVipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('vips', function (Blueprint $table) {
            $table->dropColumn(['load_balancer_id', 'network_id']);
        });

        Schema::connection('ecloud')->table('vips', function (Blueprint $table) {
            $table->uuid('load_balancer_network_id')->after('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('vips', function (Blueprint $table) {
            $table->dropColumn(['load_balancer_network_id']);
        });

        Schema::connection('ecloud')->table('vips', function (Blueprint $table) {
            $table->uuid('load_balancer_id')->after('name');
            $table->uuid('network_id')->after('load_balancer_id');
        });
    }
}
