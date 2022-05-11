<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBuilderConfigurationsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('builder_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('reseller_id');
            $table->bigInteger('employee_id');
            $table->text('data');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('builder_configurations');
    }
}
