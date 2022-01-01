<?php

namespace Database\Seeders;

use App\Models\Weeks;
use Illuminate\Database\Seeder;

class WeeksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Weeks::insert([
            ['day'=>'Sunday'],
            ['day'=>'Monday'],
            ['day'=>'Tuesday'],
            ['day'=>'Wednesday'],
            ['day'=>'Thursday'],
            ['day'=>'Friday'],
            ['day'=>'Saturday'],
        ]);
    }
}
