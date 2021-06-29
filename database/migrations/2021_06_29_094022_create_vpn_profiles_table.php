<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVpnProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('vpn_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('ike_version');
            $table->text('encryption_algorithm');
            $table->text('digest_algorithm');
            $table->text('diffie_-_hellman');
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
        Schema::connection('ecloud')->dropIfExists('vpn_profiles');
    }
}
