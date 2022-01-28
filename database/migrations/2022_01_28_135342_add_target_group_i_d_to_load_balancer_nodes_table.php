<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTargetGroupIDToLoadBalancerNodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('load_balancer_nodes', function (Blueprint $table) {
            $table->integer('target_group_id')->nullable()->after('node_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('load_balancer_nodes', function (Blueprint $table) {
            $table->dropColumn(['target_group_id']);
        });
    }
}
