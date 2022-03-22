<?php


namespace App\Http\Controllers\API;


use App\Models\Review;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends \App\Http\Controllers\Controller
{
    public function addReview(Request $request)
    {
        $user = auth()->guard('api')->user();
        $v = Validator::make( $request->all(), [
            'vendor_id' => 'required|integer|exists:vendors,id',
            'comment' => 'required|string',
            'star' => 'required|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        Review::create([
            'reviewable_id'=>$request->vendor_id,
            'reviewable_type'=>'App\Models\Vendor',
            'user_id'=> $user->id,
            'comment'=>$request->input('comment'),
            'star'=>$request->input('star')
        ]);

        return response([
            'status'=>true,
            'message'=>'Review added',
            'data'=>[]
        ]);
    }

    public function getMyReviews()
    {
        $user = auth()->guard('api')->user();

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'reviews'=>Review::where('user_id', $user->id)->all()
            ]
        ]);
    }

    public function viewReview(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'review_id' => 'required|integer|exists:reviews,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'reviews'=>Review::find($request->input('review_id'))
            ]
        ]);
    }
}
