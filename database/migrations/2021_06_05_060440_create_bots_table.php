<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('type')->default('pivot');
            $table->string('pair');
            $table->json('corresponding_orders')->nullable();
            $table->json('settings')->nullable();
            $table->char('status', 10)->default('inactive');
            $table->datetime('started_at');
            $table->timestamps();

            $table->unique('uuid');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bots');
    }
}
