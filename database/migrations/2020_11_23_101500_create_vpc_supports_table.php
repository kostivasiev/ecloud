<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVpcSupportsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('vpc_supports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vpc_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('vpc_supports');
    }
}
