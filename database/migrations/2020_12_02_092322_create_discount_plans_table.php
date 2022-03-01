<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDiscountPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('discount_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('reseller_id')->default(0);
            $table->bigInteger('contact_id')->default(0);
            $table->bigInteger('employee_id')->default(0);
            $table->string('name');
            $table->decimal('commitment_amount')->default(0.00);
            $table->decimal('commitment_before_discount', 8, 2)->default(0.00);
            $table->decimal('discount_rate')->default(0.00);
            $table->integer('term_length'); // months?
            $table->dateTime('term_start_date');
            $table->dateTime('term_end_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('discount_plans');
    }
}
