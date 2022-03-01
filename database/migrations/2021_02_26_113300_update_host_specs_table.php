<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class UpdateHostSpecsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('host_specs', function ($table) {
            $table->unsignedSmallInteger('cpu_sockets')->nullable();
            $table->string('cpu_type')->nullable();
            $table->unsignedSmallInteger('cpu_cores')->nullable();
            $table->unsignedInteger('cpu_clock_speed')->nullable();
            $table->unsignedSmallInteger('ram_capacity')->nullable();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('host_specs', function ($table) {
            $table->dropColumn([
                'cpu_sockets',
                'cpu_type',
                'cpu_cores',
                'cpu_clock_speed',
                'ram_capacity'
            ]);
        });
    }
}
