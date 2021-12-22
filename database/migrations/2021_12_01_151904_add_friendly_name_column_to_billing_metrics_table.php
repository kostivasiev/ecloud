<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFriendlyNameColumnToBillingMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('billing_metrics', function (Blueprint $table) {
            $table->string('friendly_name')->after('reseller_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('billing_metrics', function (Blueprint $table) {
            $table->dropColumn(['friendly_name']);
        });
    }
}
