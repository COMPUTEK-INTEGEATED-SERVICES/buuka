<?php


namespace App\Http\Controllers\Action;


use KingFlamez\Rave\Facades\Rave as Flutterwave;

class PaymentAction
{
    public static function initiateFlutter($data)
    {
        $payment = Flutterwave::initializePayment($data);


        if ($payment && $payment['status'] == 'success') {
            return $payment['data']['link'];
        }
        return false;
    }
}
