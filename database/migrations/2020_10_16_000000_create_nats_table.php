<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNatsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('nats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('destination');
            $table->uuid('translated');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('nats');
    }
}
