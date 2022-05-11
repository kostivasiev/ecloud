<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImageParametersTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('image_parameters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('image_id');
            $table->string('name');
            $table->string('key');
            $table->string('type');
            $table->string('description');
            $table->boolean('required')->default(true);
            $table->string('validation_rule');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('image_parameters');
    }
}
