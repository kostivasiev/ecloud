<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAvailabilityZonesTable2 extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('availability_zones', function (Blueprint $table) {
            $table->uuid('nsx_edge_cluster_id')->nullable()->after('nsx_manager_endpoint');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('availability_zones', function (Blueprint $table) {
            $table->dropColumn('nsx_edge_cluster_id');
        });
    }
}
