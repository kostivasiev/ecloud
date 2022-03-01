<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImageMetadataTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('image_metadata', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('image_id');
            $table->string('key');
            $table->text('value');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('image_metadata');
    }
}
