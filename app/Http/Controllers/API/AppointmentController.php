<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
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

    public function get_vendor_appointments_today(Request $request)
    {
        $v = Validator::make($request->all(), [
            'vendor_id'=>'required|int|exists:vendors,id'
        ]);

        if ($v->fails()){
            return response()->json([
                'status'=>false,
                'message'=>'Validation error',
                'data'=>$v->errors()
            ]);
        }

        return response()->json([
            'status'=>true,
            'message'=>'',
            'data'=>Appointment::today(Vendor::find($request->vendor_id))
        ]);
    }

    public function get_vendor_appointments(Request $request)
    {
        $v = Validator::make($request->all(), [
            'vendor_id'=>'required|int|exists:vendors,id'
        ]);

        if ($v->fails()){
            return response()->json([
                'status'=>false,
                'message'=>'Validation error',
                'data'=>$v->errors()
            ]);
        }

        return response()->json([
            'status'=>true,
            'message'=>'',
            'data'=>Appointment::where('vendor_id', $request->vendor_id)->latest()->paginate(20)
        ]);
    }
}
