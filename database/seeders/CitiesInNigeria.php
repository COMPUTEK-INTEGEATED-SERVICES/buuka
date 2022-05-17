<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CitiesInNigeria extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cities = json_decode(file_get_contents('./extras/cities_in_nigeria.json'));
        $country = \App\Models\Country::where('name', 'Nigeria')->where('initial', 'NGA')->first();
        foreach ($cities as $items)
        {
            $state = \App\Models\State::where('country_id', $country->id)->where('name', $items->name)->first();
            if ($state)
            {
                $citiez = $items->cities;
                foreach ($citiez as $city){
                    \App\Models\City::create([
                        'state_id'=>$state->id,
                        'name'=>$city
                    ]);
                }
            }
        }
    }
}
