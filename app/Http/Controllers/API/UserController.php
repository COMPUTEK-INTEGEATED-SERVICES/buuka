<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
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

    public function uploadPhoto(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'file' => 'required|mimes:jpeg,jpg,png,gif,pdf',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        $file =  $request->file('file')->store('public/photo');

        $user = User::find($this->user->id);
        $user->photo = $file;
        $file->save();

        return response()->json([
            'status' => true,
            'message' => 'Photo uploaded',
            'data' => []
        ]);
    }

    public function editUserProfile(Request $request)
    {
        $gender = ['male', 'female'];
        $v = Validator::make( $request->all(), [
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'gender' => 'nullable|string|in:'.strtolower(implode(',', $gender)),
            'date_of_birth' => 'nullable|date_format:Y-m-d|before:today'
        ]);

        if($v->fails()){
            return response()->json([
              'status' => false,
              'message' => 'Validation Error',
              'data' => $v->errors(),
            ], 422);
        }

        try {
            $user = User::find($this->user->id);
            $user->first_name = $request->input('first_name', $user->first_name);
            $user->last_name = $request->input('last_name', $user->last_name);
            //$user->email = $request->input('email', $user->email);
            //$user->email_verified = (!empty($request->email) && $user->email != $request->email)?0:$user->email_verified;
            //$user->phone = $request->input('phone', $user->phone);
            //$user->phone_verified = (!empty($request->phone) && $user->phone != $request->phone)?0:$user->phone_verified;
            $user->gender = $request->input('gender', $user->gender);
            $user->date_of_birth = $request->input('date_of_birth', $user->date_of_birth);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Profile Saved',
                'data' => []
            ]);
        }catch (\Throwable $throwable){
            report($throwable);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'data' => []
            ]);
        }
    }

    public function userLastSeen()
    {
        $user = User::find($this->user->id);
        $user->last_seen = Carbon::now();
        $user->save();

        return response()->json([
            'status' => true,
            'message' => '',
            'data' => []
        ]);


    }
}
