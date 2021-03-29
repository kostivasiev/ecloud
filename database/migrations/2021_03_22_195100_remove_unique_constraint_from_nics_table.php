<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveUniqueConstraintFromNicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Separate statement to satisfy SQLite dropping the index
        Schema::connection('ecloud')->table('nics', function (Blueprint $table) {
            $table->dropUnique('idx_unique_ip');
        });

        Schema::connection('ecloud')->table('nics', function (Blueprint $table) {
            $table->dropColumn('deleted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('nics', function (Blueprint $table) {
            $table->unique(['ip_address', 'network_id', 'deleted'], 'idx_unique_ip');
            $table->boolean('deleted')->default(false);
        });
    }
}
