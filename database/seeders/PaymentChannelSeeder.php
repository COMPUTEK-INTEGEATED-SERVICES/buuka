<?php

namespace Database\Seeders;

use App\Models\PaymentChannel;
use Illuminate\Database\Seeder;

class PaymentChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PaymentChannel::create([
            'name'=>'Paystack',
            'secret_key'=>'sk_test_29778985aa308f6dfd4720064a958f656292aade',
            'public_key'=>'pk_test_465e85530c4649b392594dad88fd070ac629b7eb'
        ]);
    }
}
