<?php


namespace App\Http\Controllers\API;


use App\Models\Category;
use App\Models\ParentCategoryRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends \App\Http\Controllers\Controller
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
            'parent_id' => 'required|int|exists:parent_categories,id',
            'file' => 'nullable|mimes:jpeg,jpg,png,gif,pdf'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('perform_admin_task', Category::class))
        {
            if($request->file){
                //upload file
                $image =  $request->file('file')->store('public/attachments/category');
            }

            $c = Category::create([
                'name'=>$request->input('name'),
                'description'=>$request->input('description'),
                'image'=>$image??null
            ]);

            ParentCategoryRelation::create([
                'parent_id'=>$request->parent_id,
                'category_id'=>$c->id
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
            'file' => 'nullable|mimes:jpeg,jpg,png,gif,pdf',
            'parent_id' => 'required|int|exists:parent_categories,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('perform_admin_task', Category::class))
        {
            if($request->file){
                //upload file
                $image =  $request->file('file')->store('public/attachments/category');
            }

            $category = Category::find($request->category_id);
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

        if ($this->user->can('perform_admin_task', Category::class))
        {
            Category::where('id', $request->input('category_id'))->delete();
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
