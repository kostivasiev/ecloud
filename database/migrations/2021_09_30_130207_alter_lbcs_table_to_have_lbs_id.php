<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterLbcsTableToHaveLbsId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('lbcs', function (Blueprint $table) {
            $table->uuid('load_balancer_spec_id')->nullable();
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
            $table->dropColumn('load_balancer_spec_id');
        });
    }
}
