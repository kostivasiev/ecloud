<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class UpdateImagesTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('images', function ($table) {
            $table->string('name')->nullable();
            $table->bigInteger('reseller_id')->nullable();
            $table->string('logo_uri')->nullable();
            $table->string('documentation_uri')->nullable();
            $table->text('description')->nullable();
            $table->text('script_template')->nullable();
            $table->text('vm_template')->nullable();
            $table->string('platform')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('public')->default(false);
            $table->string('publisher')->nullable();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('images', function ($table) {
            $table->dropColumn([
                'name',
                'reseller_id',
                'logo_uri',
                'documentation_uri',
                'description',
                'script_template',
                'vm_template',
                'platform',
                'active',
                'public',
                'publisher'
            ]);
        });
    }
}
