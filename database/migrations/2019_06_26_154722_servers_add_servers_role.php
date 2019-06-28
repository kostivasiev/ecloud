<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
