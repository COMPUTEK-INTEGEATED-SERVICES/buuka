<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;
    private $vendor;

    public function __contruct()
    {
        $this->middleware('auth:api');
        $this->user = auth()->guard('api')->auth();

        $this->vendor = Vendor::where('user_id',$this->user->id)->first();
    }

    public function vendorAddNote(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'client_id' => 'required|int|exists:clients,id',
            'vendor_note' => 'required|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('interact', $this->vendor))
        {
            Client::create([
                'vendor_note' => $request->input('vendor_note'),
            ]);

            return response([
                'status'=>true,
                'message'=>'Client Note Added',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);

    }

    public function vendorEditNote(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'client_id' => 'required|int|exists:clients,id',
            'vendor_note' => 'required|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('interact', $this->vendor))
        {
            $client = Client::find($request->input('client_id'));
            $client->vendor_note = $request->input('vendor_note',  $client->vendor_note);
            $client->save();

            return response([
                'status'=>true,
                'message'=>'Client Note Edited',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);


    }

    public function vendorDeleteNote(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'client_id' => 'required|int|exists:clients,id'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('interact', $this->vendor))
        {
            Client::find($request->input('client_id'))->delete();

            return response([
                'status'=>true,
                'message'=>'Client Note Deleted',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);


    }

    public function getClientInfo(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'vendor_id' => 'required|int|exists:clients,vendor_id',
            'client_id' => 'required|int|exists:clients,user_id'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('interact', $this->vendor))
        {
            $client = Client::where('user_id', $request->input('client_id'))
                ->where('vendor_id', $request->input('vendor_id'))->first();

            return response([
                'status'=>true,
                'message'=>'',
                'data'=>[
                    'client' => $client
                ]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }

    public function getAllClientInfo(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'vendor_id' => 'required|int|exists:clients,vendor_id'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('interact', $this->vendor))
        {
            $client = Client::where('vendor_id', $request->input('vendor_id'))->get();

            return response([
                'status'=>true,
                'message'=>'',
                'data'=>[
                    'clients' => $client
                ]
            ]);
        }
        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }
}
