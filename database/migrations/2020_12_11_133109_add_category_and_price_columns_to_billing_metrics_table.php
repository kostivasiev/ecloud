<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCategoryAndPriceColumnsToBillingMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('billing_metrics', function (Blueprint $table) {
            $table->string('category')->after('end')->nullable();
            $table->float('price')->after('category')->nullable();
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
            $table->dropColumn(['category','price']);
        });
    }
}
