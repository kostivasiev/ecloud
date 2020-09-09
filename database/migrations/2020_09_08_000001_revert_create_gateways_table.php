<?php

use Illuminate\Database\Migrations\Migration;

class RevertCreateGatewaysTable extends Migration
{
    public function up()
    {
        (new CreateGatewaysTable())->down();
    }

    public function down()
    {
        (new CreateGatewaysTable())->up();
    }
}
