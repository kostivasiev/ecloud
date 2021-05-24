<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilityZoneImageTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('availability_zone_image', function (Blueprint $table) {
            $table->uuid('image_id');
            $table->uuid('availability_zone_id');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('availability_zone_image');
    }
}
