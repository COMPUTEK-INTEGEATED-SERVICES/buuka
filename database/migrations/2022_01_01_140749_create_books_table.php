<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('vendor_id');
            $table->json('product_id');
            $table->integer('schedule')->comment('the schedule is the time and date this is meant to be delivered');
            $table->integer('amount');
            $table->text('note')->nullable();
            $table->string('type')->comment('fixed or custom');
            $table->integer('payment_method_id')->nullable();
            $table->string('proposed_by')->nullable();
            $table->tinyInteger('custom_book_accepted')->default(0);
            $table->tinyInteger('status')->default(0)->comment('0 indicating that the order is pending');
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
        Schema::dropIfExists('books');
    }
}
