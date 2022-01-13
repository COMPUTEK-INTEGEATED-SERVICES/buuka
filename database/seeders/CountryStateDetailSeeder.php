<?php


namespace Database\Seeders;


use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

class CountryStateDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //now we have used the main country json to update countries, we will use another country json to fill
        //empty fields
        $countries = json_decode(file_get_contents('./extras/countries1.json'));
        foreach ($countries as $country)
        {
            $c = Country::where('name', $country->name)->first();
            if ($c)
            {
                $c->currency = $country->currency;
                $c->currency_name = $country->currency_name;
                $c->currency_symbol = $country->currency_symbol;
                $c->phone_code = $country->phone_code;
                $c->capital = $country->capital;
                $c->region = $country->region;
                $c->sub_region = $country->subregion;
                $c->save();
            }
        }
    }
}
