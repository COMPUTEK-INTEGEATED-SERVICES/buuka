<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $banks = json_decode(file_get_contents('./extras/banks.json'));
        foreach ($banks as $bank)
        {
            Bank::create([
                'name'=>$bank->name,
                'code'=>$bank->code,
                'slug'=>$bank->slug,
                'country'=>$bank->country
            ]);
        }
    }
}
