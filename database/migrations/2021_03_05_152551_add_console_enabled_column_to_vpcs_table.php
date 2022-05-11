<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsoleEnabledColumnToVpcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('vpcs', function (Blueprint $table) {
            $table->boolean('console_enabled')->default(true)->after('reseller_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('vpcs', function (Blueprint $table) {
            $table->dropColumn(['console_enabled']);
        });
    }
}
