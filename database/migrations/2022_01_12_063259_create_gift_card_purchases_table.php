<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftCardPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gift_card_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('unit_price');
            $table->string('quantity');
            $table->enum('delivery', ['email', 'sms', 'both']);
            $table->string('to');
            $table->string('from');
            $table->string('message');
            $table->string('delivery_date');
            $table->tinyInteger('status')->default(0)
                ->comment('0-notpaid, 1-paid, 2-delivered');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gift_card_purchases');
    }
}
