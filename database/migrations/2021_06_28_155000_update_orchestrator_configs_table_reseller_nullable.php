<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrchestratorConfigsTableResellerNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('orchestrator_configs', function (Blueprint $table) {
            $table->bigInteger('reseller_id')->nullable()->change();
            $table->bigInteger('employee_id')->nullable()->change();
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
            $table->bigInteger('reseller_id')->change();
            $table->bigInteger('employee_id')->change();
        });
    }
}
