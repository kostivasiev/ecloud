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
        Schema::connection('ecloud')->table('credentials', function (Blueprint $table) {
            $table->string('resource_id')->nullable()->change();
            $table->text('password')->nullable()->change();
            $table->integer('port')->nullable()->change();
            $table->index('is_hidden');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('credentials', function (Blueprint $table) {
            $table->string('resource_id')->nullable()->change();
            $table->text('password')->nullable(false)->change();
            $table->string('port')->nullable(false)->change();
            $table->dropIndex(['is_hidden']);
        });
    }
};
