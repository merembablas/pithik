<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->char('coin', 10);
            $table->date('date_event');
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('proof')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->index('coin');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news');
    }
}
