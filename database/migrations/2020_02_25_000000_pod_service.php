<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PodService extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('pod_service', function (Blueprint $table) {
            $table->bigInteger('pod_id');
            $table->uuid('service_id');
            $table->text('service_type');
        });

        Schema::connection('ecloud')->create('pod_service_artisan', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::connection('ecloud')->create('pod_service_conjurer', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::connection('ecloud')->create('pod_service_envoy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 127);
            $table->text('url');
        });

        Schema::connection('ecloud')->create('pod_service_flint', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::connection('ecloud')->create('pod_service_kingpin', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->drop('pod_service');
        Schema::connection('ecloud')->drop('pod_service_artisan');
        Schema::connection('ecloud')->drop('pod_service_conjurer');
        Schema::connection('ecloud')->drop('pod_service_envoy');
        Schema::connection('ecloud')->drop('pod_service_flint');
        Schema::connection('ecloud')->drop('pod_service_kingpin');
    }
}
