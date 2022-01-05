<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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
}
