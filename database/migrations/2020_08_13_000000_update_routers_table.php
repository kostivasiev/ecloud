<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class UpdateRoutersTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('routers', function ($table) {
            $table->boolean('deployed')->default(false);
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('routers', function ($table) {
            $table->dropColumn('deployed');
        });
    }
}
