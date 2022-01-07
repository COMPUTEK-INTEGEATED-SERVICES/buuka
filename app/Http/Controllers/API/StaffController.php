<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;

class StaffController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->user = auth()->guard('api')->user();
    }

    public function addStaff(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'email' => 'required|string|email|max:255|unique:users'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Email Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $vendor = Vendor::where('user_id', $this->user->id )->first()->id;

        Staff::create([
            'vendor_id' => $vendor,
            'user_id' => $this->user->id,
        ]);

        return response()->json([
            'status' => false,
            'message' => 'New Staff Added',
            'data' => []
        ]);
    }

    public function confirmAddStaff()
    {

    }

    public function removeStaff()
    {

    }
}
