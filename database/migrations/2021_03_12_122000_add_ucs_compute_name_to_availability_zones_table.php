<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUcsComputeNameToAvailabilityZonesTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('availability_zones', function (Blueprint $table) {
            $table->string('ucs_compute_name')->after('nsx_edge_cluster_id')->nullable();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('availability_zones', function ($table) {
            $table->dropColumn('ucs_compute_name');
        });
    }
}
