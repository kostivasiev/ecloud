<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVolumeGroupIdToInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
            $table->uuid('volume_group_id')->nullable()->index()->after('host_group_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
            $table->dropColumn(['volume_group_id']);
        });
    }
}
