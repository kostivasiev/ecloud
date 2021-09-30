<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterVpnProfileGroupsTableToHaveNullableDescription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('vpn_profile_groups', function (Blueprint $table) {
            $table->text('description')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *v
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('vpn_profile_groups', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
        });
    }
}
