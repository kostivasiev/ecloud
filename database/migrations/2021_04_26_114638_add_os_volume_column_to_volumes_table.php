<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOsVolumeColumnToVolumesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('volumes', function (Blueprint $table) {
            $table->boolean('os_volume')->default(true)->after('vmware_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('volumes', function (Blueprint $table) {
            $table->dropColumn(['os_volume']);
        });
    }
}
