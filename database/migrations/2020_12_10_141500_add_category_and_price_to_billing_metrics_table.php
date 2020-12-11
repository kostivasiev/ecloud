<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryAndPriceToBillingMetricsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('billing_metrics', function (Blueprint $table) {
            $table->string('category')->after('end')->nullable();
            $table->float('price')->after('category')->nullable();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('billing_metrics', function (Blueprint $table) {
            $table->dropColumn(['category', 'price']);
        });
    }
}
