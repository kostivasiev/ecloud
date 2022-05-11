<?php

use Illuminate\Database\Migrations\Migration;

class RevertCreateGatewaysTable extends Migration
{
    public function up()
    {
        require_once(app()->basePath('database/migrations/2020_07_09_104211_create_gateways_table.php'));
        (new CreateGatewaysTable())->down();
    }

    public function down()
    {
        require_once(app()->basePath('database/migrations/2020_07_09_104211_create_gateways_table.php'));
        (new CreateGatewaysTable())->up();
    }
}
