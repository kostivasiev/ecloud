<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplianceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('appliance', function (Blueprint $table) {
            $table->increments('appliance_id');
            $table->string('appliance_uuid');
            $table->string('appliance_name');
            $table->string('appliance_logo_uri');
            $table->text('appliance_description');
            $table->string('appliance_documentation_uri');
            $table->string('appliance_publisher');
            $table->enum('appliance_active', ['Yes', 'No'])->default('Yes');
            $table->timestamp('appliance_created_at');
            $table->timestamp('appliance_updated_at');
            $table->timestamp('appliance_deleted_at')->nullable();
        });

        Schema::connection('ecloud')->create('appliance_version', function (Blueprint $table) {
            $table->increments('appliance_version_id');
            $table->string('appliance_version_uuid');
            $table->integer('appliance_version_appliance_id');
            $table->integer('appliance_version_version');
            $table->text('appliance_version_description');
            $table->text('appliance_version_script_template');
            $table->text('appliance_version_vm_template');
            $table->integer('appliance_version_server_license_id')->nullable();
            $table->enum('appliance_version_active', ['Yes', 'No'])->default('Yes');
            $table->timestamp('appliance_version_created_at');
            $table->timestamp('appliance_version_updated_at');
            $table->timestamp('appliance_version_deleted_at')->nullable();
        });

        Schema::connection('ecloud')->create('appliance_script_parameters', function (Blueprint $table) {
            $table->increments('appliance_script_parameters_id');
            $table->string('appliance_script_parameters_uuid');
            $table->integer('appliance_script_parameters_appliance_version_id');
            $table->string('appliance_script_parameters_name');
            $table->string('appliance_script_parameters_key');
            $table->enum('appliance_script_parameters_type',
                ['String', 'Numeric', 'Boolean', 'Array', 'Password', 'Date', 'DateTime'])->default('String');
            $table->enum('appliance_script_parameters_required', ['Yes', 'No'])->default('Yes');
            $table->string('appliance_script_parameters_description');
            $table->string('appliance_script_parameters_validation_rule')->default('');
            $table->timestamp('appliance_script_parameters_created_at');
            $table->timestamp('appliance_script_parameters_updated_at');
            $table->timestamp('appliance_script_parameters_deleted_at')->nullable();
        });

//        Schema::connection('ecloud')->create('appliance_release_notes', function (Blueprint $table) {
//            $table->increments('appliance_release_notes_id');
//            $table->string('appliance_release_notes_uuid');
//            $table->integer('appliance_release_notes_version_id');
//            $table->text('appliance_release_notes');
//            $table->timestamp('appliance_release_notes_created_at');
//            $table->timestamp('appliance_release_notes_updated_at');
//        });

        Schema::connection('ecloud')->create('appliance_pod_availability', function (Blueprint $table) {
            $table->increments('appliance_pod_availability_id');
            $table->integer('appliance_pod_availability_appliance_id');
            $table->integer('appliance_pod_availability_ucs_datacentre_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('appliance');
        Schema::connection('ecloud')->dropIfExists('appliance_version');
        Schema::connection('ecloud')->dropIfExists('appliance_script_parameters');
//        Schema::connection('ecloud')->dropIfExists('appliance_release_notes');
        Schema::connection('ecloud')->dropIfExists('appliance_pod_availability');
    }
}
