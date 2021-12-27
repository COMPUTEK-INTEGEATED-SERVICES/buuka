<?php


namespace App\Http\Controllers\API;


use App\Events\Chat\NewChatMessage;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
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

    public function sendMessage(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'message' => 'required_without:file|string',
            'to_user_id' => 'required|integer|exists:users,id',
            'file' => 'nullable|mimes:jpeg,jpg,png,gif,pdf'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => 'false',
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->canParticipate($this->user->id, $request->input('to_user_id')))
        {
            if($request->file){
                //upload file
                $message =  $request->file('file')->store('public/attachments');

                $type = $request->file('file')->getMimeType();
            }else{

                $message = $request->message;
            }

            $chat = Chat::create([
                'user_1'=>$this->user->id,
                'user_2'=>$request->input('to_user_id'),
                'type'=>$type??'text',
                'message'=>$message
            ]);

            broadcast( new NewChatMessage($chat, $this->user, User::find($request->input('to_user_id'))));

            return response([
                'status'=>true,
                'message'=>'Chat sent',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Not authorized',
            'data'=>[]
        ], 401);
    }

    private function canParticipate($user_id, $to_user_id):bool
    {
        return $user_id != $to_user_id;
    }

    public function getMessages(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'to_user_id' => 'required|integer|exists:users,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => 'false',
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        if($this->canParticipate($this->user->id, $request->input('to_user_id')))
        {
            $chat = Chat::where(function ($query) use ($request) {
                $query->where('user_1', $this->user->id)
                    ->where('user_2', $request->input('to_user_id'));
            })->where(function ($query) use ($request) {
                $query->where('user_1', $request->input('to_user_id'))
                    ->where('user_2', $this->user->id);
            })->latest()->paginate(10);

            return response([
                'status'=>true,
                'message'=>'Chat sent',
                'data'=>[
                    'user'=>$this->user,
                    'chat'=>$chat
                ]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Not authorized',
            'data'=>[]
        ], 401);
    }
}
