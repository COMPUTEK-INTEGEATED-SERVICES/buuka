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
                $query->string('currency_name')->nullalble();
                $query->string('currency_symbol')->nullalble();
                $query->string('phone_code')->nullalble();
                $query->string('capital')->nullalble();
                $query->string('region')->nullalble();
                $query->string('sub_region')->nullalble();
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
