<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveNsxEdgeClusterIdFromAvailabilityZones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('availability_zones', function (Blueprint $table) {
            $table->dropColumn('nsx_edge_cluster_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('availability_zones', function (Blueprint $table) {
            $table->uuid('nsx_edge_cluster_id')->nullable()->after('nsx_manager_endpoint');
        });
    }
}
