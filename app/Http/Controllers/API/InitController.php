<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;

class InitController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    public function __construct()
    {
        $this->user = auth()->guard('api')->user();
    }

    public function config()
    {
        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'user'=>$this->user
            ]
        ]);
    }
}
