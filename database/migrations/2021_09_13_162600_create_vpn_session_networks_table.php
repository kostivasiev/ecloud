<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVpnSessionNetworksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('vpn_session_networks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vpn_session_id')->index();
            $table->string('type');
            $table->ipAddress('ip_address');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('vpn_session_networks');
    }
}
