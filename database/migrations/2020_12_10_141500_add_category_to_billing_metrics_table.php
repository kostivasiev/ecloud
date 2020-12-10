<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceToBillingMetricsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('billing_metrics', function (Blueprint $table) {
            $table->string('category')->after('end');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('billing_metrics', function (Blueprint $table) {
            $table->string('category');
        });
    }
}
