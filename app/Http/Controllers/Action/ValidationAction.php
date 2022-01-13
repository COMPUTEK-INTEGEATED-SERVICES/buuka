<?php


namespace App\Http\Controllers\Action;


use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Validator;

class ValidationAction
{
    public static function validate($request, array $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
        {
            return response([
                'status'=>false,
                'message'=>'Validation failed',
                'data'=>$validator->errors()->all()
            ], 422);
        }
        //return true;
    }
}
