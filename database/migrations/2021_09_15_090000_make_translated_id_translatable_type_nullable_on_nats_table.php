<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeTranslatedIdTranslatableTypeNullableOnNatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('nats', function (Blueprint $table) {
            $table->uuid('translated_id')->nullable()->change();
            $table->text('translatedable_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('nats', function (Blueprint $table) {
            $table->uuid('translated_id')->nullable(false)->change();
            $table->text('translatedable_type')->nullable(false)->change();
        });
    }
}
