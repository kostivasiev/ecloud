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
        Schema::connection('ecloud')->table('instance_volume', function (Blueprint $table) {
            $table->index('instance_id', 'instance_volume_instance_id');
            $table->index('volume_id', 'instance_volume_volume_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('instance_volume', function (Blueprint $table) {
            $table->dropIndex('instance_volume_instance_id');
            $table->dropIndex('instance_volume_volume_id');
        });
    }
};
