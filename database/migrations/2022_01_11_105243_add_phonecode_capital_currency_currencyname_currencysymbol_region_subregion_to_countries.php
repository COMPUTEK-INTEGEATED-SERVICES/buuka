<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhonecodeCapitalCurrencyCurrencynameCurrencysymbolRegionSubregionToCountries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->after('currency', function ($query){
                $query->string('currency_name')->nullable();
                $query->string('currency_symbol')->nullable();
                $query->string('phone_code')->nullable();
                $query->string('capital')->nullable();
                $query->string('region')->nullable();
                $query->string('sub_region')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            //
        });
    }
}
