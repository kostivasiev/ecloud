<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLockedColumnToOrchestratorConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('orchestrator_configs', function (Blueprint $table) {
            $table->boolean('locked')->after('data')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('orchestrator_configs', function (Blueprint $table) {
            $table->dropColumn(['locked']);
        });
    }
}
