<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSshKeyPairsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('ssh_key_pairs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('reseller_id');
            $table->string('name');
            $table->text('public_key');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('ssh_key_pairs');
    }
}
