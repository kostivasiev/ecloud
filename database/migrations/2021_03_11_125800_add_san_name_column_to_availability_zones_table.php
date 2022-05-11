<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSanNameColumnToAvailabilityZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('availability_zones', function (Blueprint $table) {
            $table->string('san_name')->nullable()->after('nsx_edge_cluster_id');;
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
            $table->dropColumn('san_name');
        });
    }
}
