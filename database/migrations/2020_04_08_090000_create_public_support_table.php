<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class CreatePublicSupportTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('public_support', function ($table) {
            $table->uuid('id')->primary();
            $table->integer('reseller_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('public_support');
    }
}
