<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApprovedColumnToDiscountPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('discount_plans', function (Blueprint $table) {
            $table->dateTime('approved')->nullable()->after('term_end_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('discount_plans', function (Blueprint $table) {
            $table->dropColumn('approved');
        });
    }
}
