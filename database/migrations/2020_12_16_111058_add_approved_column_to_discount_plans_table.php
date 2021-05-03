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
            $table->string('status')->default('pending')->after('term_end_date');
            $table->timestamp('response_date')->nullable()->after('status');
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
            $table->dropColumn(['status', 'response_date']);
        });
    }
}
