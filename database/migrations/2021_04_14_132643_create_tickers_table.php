<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTickersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickers', function (Blueprint $table) {
            $table->id();
            $table->char('pair', 15);
            $table->char('timeframe', 4);
            $table->bigInteger('price_iddx');
            $table->bigInteger('price_cmc');
            $table->bigInteger('price_bnc');
            $table->bigInteger('vol_iddx');
            $table->timestamps();

            $table->index('pair');
            $table->index('timeframe');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickers');
    }
}
