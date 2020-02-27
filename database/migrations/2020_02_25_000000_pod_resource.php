<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PodResource extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('pod_resource', function (Blueprint $table) {
            $table->bigInteger('pod_id');
            $table->uuid('resource_id');
            $table->text('resource_type');
        });

        Schema::connection('ecloud')->create('pod_resource_artisan', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::connection('ecloud')->create('pod_resource_conjurer', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::connection('ecloud')->create('pod_resource_envoy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->binary('token');
            $table->text('url');
        });

        Schema::connection('ecloud')->create('pod_resource_flint', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::connection('ecloud')->create('pod_resource_kingpin', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->drop('pod_resource');
        Schema::connection('ecloud')->drop('pod_resource_artisan');
        Schema::connection('ecloud')->drop('pod_resource_conjurer');
        Schema::connection('ecloud')->drop('pod_resource_envoy');
        Schema::connection('ecloud')->drop('pod_resource_flint');
        Schema::connection('ecloud')->drop('pod_resource_kingpin');
    }
}
