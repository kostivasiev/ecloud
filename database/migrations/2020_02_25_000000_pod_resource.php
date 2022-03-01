<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

        Schema::connection('ecloud')->create('pod_resource_compute', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::connection('ecloud')->create('pod_resource_console', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->binary('token');
            $table->text('url');
            $table->text('console_url');
        });

        Schema::connection('ecloud')->create('pod_resource_management', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::connection('ecloud')->create('pod_resource_network', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        Schema::connection('ecloud')->create('pod_resource_storage', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->drop('pod_resource');
        Schema::connection('ecloud')->drop('pod_resource_compute');
        Schema::connection('ecloud')->drop('pod_resource_console');
        Schema::connection('ecloud')->drop('pod_resource_management');
        Schema::connection('ecloud')->drop('pod_resource_network');
        Schema::connection('ecloud')->drop('pod_resource_storage');
    }
}
