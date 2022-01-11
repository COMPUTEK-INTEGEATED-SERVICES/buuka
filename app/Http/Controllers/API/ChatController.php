<?php


namespace App\Http\Controllers\API;


use App\Events\Chat\NewChatMessage;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'file' => 'nullable|mimes:jpeg,jpg,png,gif,pdf',
            'book'=>'nullable|array',
            'book.product'=>'integer|exists:products,id',
            'book.amount'=>'string',
            'book.scheduled'=>'string',
            'book.extras'=>'string',
            'book.vendor_id'=>'int',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->senderIsNotReceiver($this->user->id, $request->input('to_user_id')))
        {
            if($request->file){
                //upload file
                $message =  $request->file('file')->store('public/attachments');

                $type = $request->file('file')->getMimeType();
            }else{

                $message = $request->message;
            }

            if ($request->book)
            {
                $message = (new OrderController())->customBook($request->input('book'))->id;
                //type is book to show that a book was made here
                $type = 'book';
            }

            $chat = Chat::create([
                'user_1'=>$this->user->id,
                'user_2'=>$request->input('to_user_id'),
                'type'=>$type??'text',
                'message'=>$message
            ]);

            try {
                broadcast( new NewChatMessage($chat, $this->user, User::find($request->input('to_user_id'))));
            }catch (\Throwable $throwable){
                report($throwable);
            }

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

    private function senderIsNotReceiver($user_id, $to_user_id):bool
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
                'status' => false,
                'message' => 'Validation error',
                'data' => $v->errors()
            ], 422);
        }

        if($this->senderIsNotReceiver($this->user->id, $request->input('to_user_id')))
        {
            $chat = Chat::with(['user_1', 'user_2'])->where(function ($query) use ($request) {
                $query->where('user_1', $this->user->id)
                    ->where('user_2', $request->input('to_user_id'));
            })->orWhere(function ($query) use ($request) {
                $query->where('user_1', $request->input('to_user_id'))
                    ->where('user_2', $this->user->id);
            })->latest()->paginate(10);

            if ($this->user->can('participate', $chat))
            {
                return response([
                    'status'=>true,
                    'message'=>'',
                    'data'=>[
                        'chat'=>$chat
                    ]
                ]);
            }
            return response([
                'status'=>false,
                'message'=>'Access denied',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Not authorized',
            'data'=>[]
        ], 401);
    }

    public function getStarredMessages(Request $request)
    {
        $chat = Chat::with(['user_1', 'user_2'])->where(function ($query) use ($request) {
            $query->where('user_1', $this->user->id)
                ->where('user_2', '!=', $this->user->id);
        })->orWhere(function ($query) use ($request) {
            $query->where('user_1', '!=', $this->user->id)
                ->where('user_2', $this->user->id);
        })->where('starred', 1)
            ->latest()->paginate(10);

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'chat'=>$chat
            ]
        ]);
    }

    public function getAllMessages()
    {
        /*$chat = Chat::with(['user_1', 'user_2'])->where('user_2', $this->user->id)
            ->latest()->distinct('user_1')->paginate(10);*/
        $chat =  Chat::with(['user_1', 'user_2'])->select(DB::raw('t.*'))
            ->from(DB::raw('(SELECT * FROM chats ORDER BY created_at DESC) t'))
            ->where('user_2', $this->user->id)
            ->groupBy('t.user_1')
            ->get();

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'chat'=>$chat
            ]
        ]);
    }

    public function getDeletedMessages(Request $request)
    {
        $chat = Chat::with(['user_1', 'user_2'])->where(function ($query) use ($request) {
            $query->where('user_1', $this->user->id)
                ->where('user_2', '!=', $this->user->id);
        })->orWhere(function ($query) use ($request) {
            $query->where('user_1', '!=', $this->user->id)
                ->where('user_2', $this->user->id);
        })->where('deleted', 1)
            ->latest()->paginate(10);

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'chat'=>$chat
            ]
        ]);
    }

    public function getNewMessages(Request $request)
    {
        $chat = Chat::with(['user_1', 'user_2'])->where(function ($query) use ($request) {
            $query->where('user_1', $this->user->id)
                ->where('user_2', '!=', $this->user->id);
        })->orWhere(function ($query) use ($request) {
            $query->where('user_1', '!=', $this->user->id)
                ->where('user_2', $this->user->id);
        })->where('read', 1)
            ->latest()->paginate(10);

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'chat'=>$chat
            ]
        ]);
    }
}
