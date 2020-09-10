<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEpgTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('epg', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignId('provider_id')->constrained('providers');
            $table->string('tvg_id', 60);
            $table->string('name', 255);
            $table->integer('time_from');
            $table->integer('time_to');
            $table->text('description')->nullable();
            $table->timestamps();
            //Creating Index
            $table->primary('id');
            $table->index('tvg_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('epg');
    }
}
