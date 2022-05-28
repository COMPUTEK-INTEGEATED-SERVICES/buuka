<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function successResponse($data=[], $message="success"): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status'=>true,
            'message'=>$message,
            'data'=>$data
        ]);
    }

    public function errorResponse($data=[], $message="Something went wrong, our engineers are on it", $errorCode = 500): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status'=>false,
            'message'=>$message,
            'data'=>$data
        ], $errorCode);
    }

    public function validationErrorResponse($data=[], $message="Validation failed", $errorCode = 422): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status'=>false,
            'message'=>'Validation error',
            'data'=>$data
        ], $errorCode);
    }
}
