<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveSupportEnabledFromVpcs extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('vpcs', function (Blueprint $table) {
            $table->dropColumn('support_enabled');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('vpcs', function (Blueprint $table) {
            $table->boolean('support_enabled')->default(false)->after('console_enabled');
        });
    }
}
