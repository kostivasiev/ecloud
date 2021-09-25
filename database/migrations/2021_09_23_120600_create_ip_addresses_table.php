<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIpAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('ip_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->ipAddress('ip_address');
            $table->string('type');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['ip_address', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('ip_addresses');
    }
}
