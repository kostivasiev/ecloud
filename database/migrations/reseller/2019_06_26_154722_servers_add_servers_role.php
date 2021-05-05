<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class ServersAddServersRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servers', function ($table) {
            $table->enum('servers_role', ['N/A', 'Web Server', 'Mail Server', 'SQL Server'])->default('N/A');
        });
    }
}
