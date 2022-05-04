<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ParentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParentCategoryController extends Controller
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

    public function addCategory(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'file' => 'required|mimes:jpeg,jpg,png,gif,pdf'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('perform_admin_task', ParentCategory::class))
        {
            if($request->file){
                //upload file
                $image =  $request->file('file')->store('public/attachments/category');
            }

            ParentCategory::create([
                'name'=>$request->input('name'),
                'description'=>$request->input('description'),
                'image'=>$image??null
            ]);

            return response([
                'status'=>true,
                'message'=>'Category created',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }

    public function editCategory(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'file' => 'nullable|mimes:jpeg,jpg,png,gif,pdf'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('perform_admin_task', ParentCategory::class))
        {
            if($request->file){
                //upload file
                $image =  $request->file('file')->store('public/attachments/category');
            }

            $category = ParentCategory::find($request->category_id);
            $category->name = $request->input('name', $category->name);
            $category->description = $request->input('description', $category->description);
            $category->image = $image ?? $category->image;
            $category->save();

            return response([
                'status'=>true,
                'message'=>'Category updated',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }

    public function deleteCategory(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('perform_admin_task', ParentCategory::class))
        {
            ParentCategory::where('id', $request->input('category_id'))->delete();
            return response([
                'status'=>true,
                'message'=>'Category deleted',
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
