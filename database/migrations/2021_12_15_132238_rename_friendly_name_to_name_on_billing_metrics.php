<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameFriendlyNameToNameOnBillingMetrics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('billing_metrics', function (Blueprint $table) {
            $table->renameColumn('friendly_name', 'name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('billing_metrics', function (Blueprint $table) {
            $table->renameColumn('name', 'friendly_name');
        });
    }
}
