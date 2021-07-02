<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeployOnColumnToOrchestratorConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('orchestrator_configs', function (Blueprint $table) {
            $table->timestamp('deploy_on')->nullable()->after('data');
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
            $table->dropColumn(['deploy_on']);
        });
    }
}
