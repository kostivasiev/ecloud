<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('floating_ip_resource', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('floating_ip_id')->index();
            $table->uuid('resource_id')->index();
            $table->string('resource_type');
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
        Schema::connection('ecloud')->dropIfExists('floating_ip_resource');
    }
};
