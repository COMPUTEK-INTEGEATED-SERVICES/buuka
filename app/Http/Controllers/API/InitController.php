<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Vendor;

class InitController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->user = auth()->guard('api')->user();
    }

    public function config()
    {
        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'user'=>$this->user,
                'notifications'=>$this->user->unreadNotifications,
                'vendor'=>[
                    'is_vendor'=> boolval((Vendor::where('user_id', $this->user->id)->first())?1:0),
                    'vendor'=> Vendor::where('user_id', $this->user->id)->first()??null,
                ],
               'admin'=>[
                   'is_admin'=>$this->user->hasRole('admin'),
                   'admin'
               ],
               'wallet'=>$this->user->wallet,
            ]
        ]);
    }
}
