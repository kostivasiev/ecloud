<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolymorphicRelationshipToFloatingIpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->string('resource_type')->nullable();
            $table->uuid('resource_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->dropColumn(['resource_type', 'resource_id']);
        });
    }
}
