<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use Laravel\Socialite\Facades\Socialite;

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

/*Route::get('test' ,function (){
    return Socialite::driver('google')->stateless()->redirect();
});*/

Route::get('callback-url', function (\Illuminate\Http\Request $request){
    $p = Socialite::driver('google')->stateless()->user();
    /*$name = $p->getName();
    $email = $p->getEmail();

    $names = explode($name, " ");
    $first_name = $names[0];
    $last_name = $names[1];*/

    //$user = \App\Models\User::
    dd($p);
});
Route::get('banks', function (){
    $url = "https://api.flutterwave.com/v3/banks/NG";
    $response = Http::withToken(env('FLW_SECRET_KEY'))->get($url);
    //$response = file_get_contents($url);
    //var_dump($response->json()['data']);
    //exit();
    $country = \App\Models\Country::where('name', 'Nigeria')->first()->id;
    $response = $response->json()['data'];
    foreach ($response as $datum){
        \App\Models\Bank::create([
            'name'=>$datum['name'],
            'code'=>$datum['code'],
            'country'=>$country
        ]);
    }
});

/*Route::get('test', function (){
    $g = file_get_contents('https://data.humdata.org/dataset/e66dbc70-17fe-4230-b9d6-855d192fc05c/resource/83dba4b0-992f-4748-b037-4b55ecc0c3b4/download/nigeria_lga.json');
    $g = json_decode($g)->features;
    foreach ($g as $key => $value){
        //var_dump($value->properties);
        if ($value->properties->NAME_1 === 'Federal Capital Territory'){
            \App\Models\City::create([
                'state_id'=>2019,
                'name'=>$value->properties->NAME_2
            ]);
        }
    }
});*/
