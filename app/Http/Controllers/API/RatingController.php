<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Rating;
use App\Models\Service;
use App\Models\Vendor;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
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

    public function rateVendor(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'rateable_id' => 'required|int|exists:vendors,id',
            'comment'=>'string',
            'rating'=>'required|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $vendor = Vendor::where('id', $request->rateable_id)->first();
        $rateable_type = 'App\Models\Vendor';

        Rating::create([
            'user_id' => $this->user->id,
            'rateable_id' => $vendor->id,
            'rateable_type' => $rateable_type,
            'comment' => $request->input('comment'),
            'rating' => $request->input('rating')
        ]);

        return response([
            'status'=>true,
            'message'=>'Rating Successful',
            'data'=>[]
        ]);

    }

    public function rateService(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'rateable_id' => 'required|int|exists:services,id',
            'comment'=>'string',
            'rating'=>'required|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $service = Service::where('id', $request->rateable_id)->first();
        $rateable_type = 'App\Models\Service';

        Rating::create([
            'user_id' => $this->user->id,
            'rateable_id' => $service->id,
            'rateable_type' => $rateable_type,
            'comment' => $request->input('comment'),
            'rating' => $request->input('rating')
        ]);

        return response([
            'status'=>true,
            'message'=>'Rating Successful',
            'data'=>[]
        ]);

    }

    public function deleteRating(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'rating_id' => 'required|int|exists:ratings,id'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $rating = Rating::find($request->rating_id);

        if ($this->user->can('interact', $rating ))
        {
            $rating->delete();

            return response([
                'status'=>true,
                'message'=>'Rating Deleted',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);


    }

}
