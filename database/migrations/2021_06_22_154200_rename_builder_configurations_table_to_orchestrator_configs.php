<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameBuilderConfigurationsTableToOrchestratorConfigs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('builder_configurations', function (Blueprint $table) {
            $table->rename('orchestrator_configs');
            $table->text('data')->nullable()->change();
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
            $table->rename('builder_configurations');
            $table->text('data')->change();
        });
    }
}
