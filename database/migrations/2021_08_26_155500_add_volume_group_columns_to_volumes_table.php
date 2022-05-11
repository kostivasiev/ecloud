<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVolumeGroupColumnsToVolumesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('volumes', function (Blueprint $table) {
            $table->boolean('is_shared')->default(false)->after('os_volume');
            $table->uuid('volume_group_id')->nullable()->after('is_shared');
            $table->tinyInteger('port')->nullable()->after('volume_group_id');
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
            $table->dropColumn(['is_shared', 'volume_group_id', 'port']);
        });
    }
}
