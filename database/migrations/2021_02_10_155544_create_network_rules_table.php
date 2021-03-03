<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNetworkRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('network_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('network_policy_id');
            $table->string('name');
            $table->integer('sequence');
            $table->string('source');
            $table->string('destination');
            $table->string('action');
            $table->boolean('enabled');
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
        Schema::connection('ecloud')->dropIfExists('network_rules');
    }
}
