<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLbsIdToLbcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('lbcs', function (Blueprint $table) {
            $table->uuid('lbs_id')->after('name');
            $table->dropColumn(['nodes']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('lbcs', function (Blueprint $table) {
            $table->dropColumn(['lbs_id']);
        });
    }
}