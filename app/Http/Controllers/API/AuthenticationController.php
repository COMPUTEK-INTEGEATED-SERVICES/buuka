<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Action\AuthenticationAction;
use App\Http\Controllers\Action\ValidationAction;
use App\Http\Controllers\Controller;
use App\Models\RegistrationVerification;
use App\Models\User;
use App\Models\PasswordReset;
use App\Models\Wallet;
use App\Notifications\Auth\EmailVerificationNotification;
use App\Notifications\Auth\PhoneVerificationNotification;
use App\Notifications\Auth\RegistrationNotification;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthenticationController extends Controller
{
    public function login (Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
        ]);

        if($v->fails()){
            return $this->validationErrorResponse($v->errors());
        }

        if (!auth()->attempt($request->all()))
        {
            return $this->validationErrorResponse([], 'Invalid credentials');
        }

        if (app('general_settings')->email_verify == 1)
        {
            if (auth()->user()->email_verified == 0)
            {
                $verification = RegistrationVerification::firstOrNew([
                    'user_id'=>auth()->user()->id
                ]);
                $otp = random_int(100000, 999999);
                //send verification code to email
                $verification->email_otp = Hash::make($otp);
                auth()->user()->notify(new EmailVerificationNotification($otp));
                $verification->save();

                $require['email']=true;
                $msg = 'Please verify your email';
            }
        }

        if (!empty($require))
        {
            $this->errorResponse([
                'email'=>auth()->user()->email,
                'phone'=>auth()->user()->phone,
                'required'=>$require
            ], $msg, 403);
        }

        $token = (new AuthenticationAction())->returnToken(auth()->user());
        return $this->successResponse(['token'=>$token], 'Logged in');
    }

    public function register (Request $request) {
        $gender = ['male', 'female'];
        $v = Validator::make( $request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone'=> "required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:users",
            //'date_of_birth' => 'required|date_format:Y-m-d|before:'.now()->subYears(12)->toDateString(),
            'gender' => 'required|string|in:'.strtolower(implode(',', $gender)),
            'city_id'=>'nullable|int|exists:cities,id',
            'country_id'=>'nullable|int|exists:countries,id',
            'state_id'=>'nullable|int|exists:states,id',
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
        $request['last_seen'] = Carbon::now();
        $user = User::create($request->toArray());

        //This will handle creating a wallet for the user
        Wallet::create([
            'walletable_id'=>$user->id,
            'walletable_type'=>'App\Models\User'
        ]);

        try {
            $user->notify(new RegistrationNotification());

            $verification = RegistrationVerification::firstOrNew([
                'user_id'=>$user->id
            ]);
            $otp = random_int(100000, 999999);
            //send verification code to email
            $verification->email_otp = Hash::make($otp);
            $user->notify(new EmailVerificationNotification($otp));

            $verification->save();
        }catch (\Throwable $throwable)
        {
            report($throwable);
        }
        return response([
            'status'=>true,
            'message'=>'Registration is successful',
            'data'=>$user
        ]);
    }

    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        return $this->successResponse([], 'Logged out');
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

    public function verifyRegistrationEmailOrPhone(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'email' => 'required|string|exists:users,email',
            'email_otp' => 'required_without:sms_otp|int|min:6',
            'sms_otp' => 'required_without:email_otp|int|min:6',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP supplied',
                'data' => $v->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user)
        {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email address',
                'data' => []
            ], 403);
        }
        $otps = RegistrationVerification::where('user_id', $user->id)->first();
        if (app('general_settings')->sms_verify == 1 && $user->phone_verified == 0 && $request->sms_otp)
        {
            if (!Hash::check($request->sms_otp, $otps->sms_otp))
            {
                $require = ['sms'];
                return response([
                    'status'=>false,
                    'message'=>'Invalid OTP supplied',
                    'data'=>[]
                ], 403);
            }
            $user->phone_verified = 1;
        }

        if (app('general_settings')->email_verify == 1 && $user->email_verified == 0 && $request->email_otp)
        {
            if (!Hash::check($request->email_otp, $otps->email_otp))
            {
                return response([
                    'status'=>false,
                    'message'=>'Invalid OTP supplied',
                    'data'=>[]
                ], 403);
            }
            $user->email_verified = 1;
        }

        $user->save();
        return response([
            'status'=>true,
            'message'=>'Verification complete',
            'data'=>[]
        ]);
    }

    public function resendSmsVerification(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'email' => 'required|string|exists:users',
            'phone'=> "nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:users"
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user)
        {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email address',
                'data' => []
            ], 403);
        }
        $verification = RegistrationVerification::firstOrCreate([
            'user_id'=>$user->id
        ]);
        if (app('general_settings')->sms_verify == 1 && $user->phone_verified == 0)
        {
            //send verification code to sms
            $otp = random_int(100000, 999999);
            //send verification code to email
            $verification->sms_otp = Hash::make($otp);
            $message = "Welcome to ". getenv('APP_NAME'). " here is your OTP:".$otp;
            $user->notify(new PhoneVerificationNotification($otp));
        }
        if ($request->phone)
        {
            $user->phone = $request->phone;
        }
        $user->save();
        $verification->save();

        return response([
            'status'=>true,
            'message'=>'OTP has been sent to '.$request->input('phone', $user->phone),
            'data'=>[]
        ]);
    }

    public function resendEmailVerification(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'email' => 'required|string|email|max:255',
            'new_email' => 'nullable|string|email|max:255|unique:users',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user)
        {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email address',
                'data' => []
            ], 403);
        }
        if ($request->new_email)
        {
            $user->email = $request->email;
        }
        $user->save();
        $verification = RegistrationVerification::firstOrCreate([
            'user_id'=>$user->id
        ]);
        if (app('general_settings')->email_verify == 1 && $user->email_verified == 0)
        {
            $otp = random_int(100000, 999999);
            //send verification code to email
            $verification->email_otp = Hash::make($otp);
            $user->notify(new EmailVerificationNotification($otp));
        }
        $verification->save();
        return response([
            'status'=>true,
            'message'=>'OTP has been sent to '.$user->email,
            'data'=>[]
        ]);
    }

    public function googleOAUTHRegister(Request $request)
    {
        try {
            //$p = Socialite::driver('google')->stateless()->user();
            $p = Socialite::driver('google')->userFromToken($request->token);
            $email = $p->getEmail();

            $first_name = ucfirst($p->user['given_name']);
            $last_name = ucfirst($p->user['family_name']);

            $user = User::firstOrNew(['email' => $email]);

            if (!$user) {
                $user->first_name = $first_name;
                $user->last_name = $last_name;
                $user->email_verified = 1;
                $user->save();
            }

            $require['gender'] = true;
            /*if (app('general_settings')->sms_verify == 1)
            {
                $require['sms']=true;
            }*/
            $token = (new AuthenticationAction())->returnToken($user);
            return response([
                'status'=>true,
                'message'=>'Logged in',
                'data'=>[
                    'token'=>$token,
                    'required'=>$require,
                ]
            ]);
        }catch (\Throwable $throwable){
            report($throwable);
            return response([
                'status'=>false,
                'message'=>'An error occurred',
                'data'=>[]
            ]);
        }
    }

    public function facebookOAUTHRegister(Request $request)
    {
        try {
            //$p = Socialite::driver('google')->stateless()->user();
            $p = Socialite::driver('facebook')->userFromToken($request->token);
            $email = $p->getEmail();

            $first_name = ucfirst($p->user['given_name']);
            $last_name = ucfirst($p->user['family_name']);

            $user = User::firstOrNew(['email' => $email]);

            if (!$user) {
                $user->first_name = $first_name;
                $user->last_name = $last_name;
                $user->email_verified = 1;
                $user->save();
            }

            $require['gender'] = true;
            /*if (app('general_settings')->sms_verify == 1)
            {
                $require['sms']=true;
            }*/
            $token = (new AuthenticationAction())->returnToken($user);
            return response([
                'status'=>true,
                'message'=>'Logged in',
                'data'=>[
                    'token'=>$token,
                    'required'=>$require,
                ]
            ]);
        }catch (\Throwable $throwable){
            report($throwable);
            return response([
                'status'=>false,
                'message'=>'An error occurred, our engineers have been notified',
                'data'=>[]
            ]);
        }
    }
}
