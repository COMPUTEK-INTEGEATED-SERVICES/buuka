<?php

use Illuminate\Support\Facades\Route;
use KingFlamez\Rave\Facades\Rave as Flutterwave;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('test' ,function (){
    $cities = json_decode(file_get_contents('../extras/cities_in_nigeria.json'));
    $country = \App\Models\Country::where('name', 'Nigeria')->where('initial', 'NGA')->first();
    foreach ($cities as $items)
    {
        $state = \App\Models\State::where('country_id', $country->id)->where('name', $items->name)->first();
        if ($state)
        {
            $citiez = $items->cities;
            foreach ($citiez as $city){
                echo $city . "<br>";
            }
        }
    }
});
