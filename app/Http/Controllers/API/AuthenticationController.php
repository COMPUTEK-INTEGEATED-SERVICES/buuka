<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Action\ValidationAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordReset;
use App\Notifications\Auth\RegistrationNotification;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthenticationController extends Controller
{
    public function login (Request $request) {

        $v = Validator::make( $request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Registration Failed',
                'data' => $v->errors()
            ], 422);
        }

        if (!auth()->attempt($request->all()))
        {
            return response([
                'status'=>false,
                'message'=>'Invalid credentials',
                'data'=>[]
            ], 422);
        }

        $token = auth()->user()->createToken(Str::random(5))->accessToken;
        return response([
            'status'=>true,
            'message'=>'Logged in',
            'data'=>[
                'token'=>$token
            ]
        ]);
    }

    public function register (Request $request) {
        $v = Validator::make( $request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone'=> "required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:users"
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Registration Failed',
                'data' => $v->errors()
            ], 422);
        }

        $request['password']=Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        User::create($request->toArray());
        try {
            $this->user->notify(new RegistrationNotification());
        }catch (\Throwable $throwable)
        {
            report($throwable);
        }
        return response([
            'status'=>true,
            'message'=>'Registration is successful',
            'data'=>[]
        ]);
    }

    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        return response([
            'status'=>true,
            'message'=>'Logged out',
            'data'=>[]
        ]);
    }

    public function sendPasswordResetToken(Request $request)
    {
        ValidationAction::validate($request, ['email'=>'required|string|email|max:255']);

        $token = Str::upper(Str::random(8));
        $tokenHash = Hash::make($token);

        PasswordReset::updateOrCreate(
            ['email' =>  request('email')],
            ['token' => $tokenHash]
        );

        $user = User::where('email', $request->email)->first();

        try {
            Notification::send($user, new PasswordResetNotification($token));
            return response([
                'status'=>true,
                'message'=>'Token is sent',
                'data'=>[]
            ]);
        }catch (\Throwable $throwable)
        {
            report($throwable);
        }
        return response([
            'status'=>false,
            'message'=>'An error occurred on the server',
            'data'=>[]
        ]);
    }

    public function submitPasswordResetToken(Request $request)
    {
        ValidationAction::validate($request, [
            'email'=>'required|string|email|max:255',
            'token'=>'required|string',
            'password'=>'required|string|min:6|confirmed'
        ]);

        $token = PasswordReset::where('email', $request->email)
            ->where('token', $request->token)->first();
        if ($token)
        {
            $user = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();

            return response([
                'status'=>true,
                'message'=>'Password reset success',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'An error occurred our engineers are working on it',
            'data'=>[]
        ]);
    }
}
