<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDpdProfileIdFromVpnProfileGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vpn_profile_groups', function (Blueprint $table) {
            $table->dropColumn('dpd_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vpn_profile_groups', function (Blueprint $table) {
            $table->uuid('dpd_profile_id')->index();
        });
    }
}
