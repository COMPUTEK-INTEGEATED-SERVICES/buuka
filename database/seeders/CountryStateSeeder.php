<?php


namespace Database\Seeders;


use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

class CountryStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $countries = json_decode(file_get_contents('./extras/countries.json'));
        foreach ($countries as $country)
        {
            $c = Country::where('name', $country->name)->firstOr(function () use ($country) {
                return Country::create([
                    'name'=>$country->name,
                    'initial'=>$country->code3,
                    'currency'=>''
                ]);
            });

            foreach ($country->states as $state)
            {
                State::where('country_id', $c->id)->firstOr(function () use ($c, $state) {
                    return State::create([
                        'country_id'=>$c->id,
                        'name'=>$state->name
                    ]);
                });
            }
        }
    }
}
