<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstanceVolumeTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('instance_volume', function (Blueprint $table) {
            $table->uuid('instance_id');
            $table->uuid('volume_id');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('instance_volume');
    }
}
