<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ApplianceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appliance', function (Blueprint $table) {
            $table->increments('appliance_id');
            $table->string('appliance_uuid');
            $table->string('appliance_name');
            $table->string('appliance_logo_url');
            $table->text('appliance_description');
            $table->string('appliance_documentation_uri');
            $table->string('appliance_publisher');
            $table->enum('appliance_active', ['Yes', 'No'])->default('Yes');
            $table->timestamp('appliance_created_at');
            $table->timestamp('appliance_updated_at');
            $table->timestamp('appliance_deleted_at');
        });

        Schema::create('appliance_version', function (Blueprint $table) {
            $table->increments('appliance_version_id');
            $table->string('appliance_version_uiud');
            $table->string('appliance_version_version');
            $table->text('appliance_version_script_template');
            $table->enum('appliance_version_active', ['Yes', 'No'])->default('Yes');
            $table->timestamp('appliance_version_created_at');
            $table->timestamp('appliance_version_updated_at');
            $table->timestamp('appliance_version_deleted_at');
        });

        Schema::create('appliance_script_parameters', function (Blueprint $table) {
            $table->increments('appliance_script_parameter_id');
            $table->string('appliance_script_parameter_uuid');
            $table->integer('appliance_script_parameter_appliance_version_id');
            $table->enum('appliance_Script_parameter_type', ['String','Numeric','Boolean','Array','Password','Date','DateTime'])->default('String');
            $table->enum('appliance_script_parameter_required', ['Yes', 'No'])->default('Yes');
            $table->string('appliance_script_parameter_validation_rule')->default('');
            $table->timestamp('appliance_script_parameter_created_at');
            $table->timestamp('appliance_script_parameter_updated_at');
        });

        Schema::create('appliance_release_notes', function (Blueprint $table) {
            $table->increments('appliance_release_notes_id');
            $table->string('appliance_release_notes_uuid');
            $table->integer('appliance_release_notes_version_id');
            $table->text('appliance_release_notes');
            $table->timestamp('appliance_release_notes_created_at');
            $table->timestamp('appliance_release_notes_updated_at');
        });

        Schema::create('appliance_pod_availability', function (Blueprint $table) {
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
        Schema::dropIfExists('appliance');
        Schema::dropIfExists('appliance_version');
        Schema::dropIfExists('appliance_script_parameters');
        Schema::dropIfExists('appliance_release_notes');
        Schema::dropIfExists('appliance_pod_availability');
    }
}
