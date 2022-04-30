<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Book;
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
        try {
            $vendor = Vendor::with(['accounts', 'wallet'])->where('user_id', $this->user->id)->first();
            return response([
                'status'=>true,
                'message'=>'',
                'data'=>[
                    'user'=>$this->user,
                    'notifications'=>$this->user->unreadNotifications,
                    'bank_accounts'=>$this->user->accounts,
                    'vendor'=>[
                        'is_vendor'=> boolval(($vendor)?1:0),
                        'vendor'=> $vendor??null,
                        'pending_sales'=>($vendor)?Book::pendingSales($vendor->id):null,
                        'in_process_sales'=>($vendor)?Book::inProgress($vendor->id):null,
                        'total_sales'=>($vendor)?Book::totalSales($vendor->id):null,
                        'total_bookings'=>($vendor)?Book::totalBookings($vendor->id):null,
                        'active_bookings'=>($vendor)?Book::activeBookings($vendor->id):null,
                        'pending_sales_amount'=>($vendor)?Book::pendingSalesAmount($vendor->id):null,
                        'total_sales_amount'=>($vendor)?Book::totalSalesAmount($vendor->id):null,
                    ],
                    'admin'=>[
                        'is_admin'=>$this->user->hasRole('admin'),
                        'admin'
                    ],
                    'wallet'=>$this->user->wallet,
                ]
            ]);
        }catch (\Throwable $throwable){
            report($throwable);
        }
    }
}
