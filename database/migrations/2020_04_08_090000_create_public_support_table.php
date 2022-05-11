<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreatePublicSupportTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('public_support', function ($table) {
            $table->uuid('id')->primary();
            $table->integer('reseller_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('public_support');
    }
}
