<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStaffRelationship;
use App\Models\Resource;
use App\Models\Service;
use App\Models\Vendor;
use App\Notifications\Product\ProductCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
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

    public function createProduct(Request $request)
    {
        $gender = ['male', 'female','all'];
        $price_type = ['from'];
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'name'=>'required|string',
            'description'=>'required|string',
            'gender'=>'required|string|in:'.strtolower(implode(',',$gender)),
            'duration'=>'string|required',
            'price'=>'string|required',
            'price_type'=>'required|string|in:'.strtolower(implode(',',$price_type)),
            'price_name'=>'nullable|string',
            'file'=>'nullable|array',
            'file.*' => 'required_with:file|mimes:jpeg,jpg,png',
            'staff'=>'nullable|array',
            'staff.*'=>'required_with:staff|int',
            'tax'=>'required|numeric'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('interact', Vendor::find(Service::find($request->service_id)->vendor_id)))
        {
            $product = Product::create($request->toArray());
            if($request->file){
                //upload file
                foreach ($request->file as $file)
                {
                    $message =  $file->store('public/attachments/products');
                    Resource::create([
                        'path'=>$message,
                        'resourceable_id'=>$product->id,
                        'resourceable_type'=>'App\Models\Product'
                    ]);
                }
            }

            if (is_array($request->staff) && !empty($request->staff))
            {
                foreach ($request->staff as $staff)
                {
                    ProductStaffRelationship::create([
                        'product_relation_id'=>$product->id,
                        'product_relation_type'=>'App\Models\Product',
                        'staff_id'=>$staff
                    ]);
                }
            }

            try {
                $this->user->notify(new ProductCreatedNotification($product));
            }catch (\Throwable $throwable)
            {
                report($throwable);
            }

            return response([
                'status'=>true,
                'message'=>'Product created',
                'data'=>[
                    'product'=>$product,
                ]
            ]);
        }
        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }

    public function editProduct(Request $request)
    {
        $gender = ['male', 'female'];
        $price_type = ['from'];
        $v = Validator::make( $request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            //'service_id' => 'required|integer|exists:services,id',
            'name'=>'nullable|string',
            'description'=>'required|string',
            'gender'=>'nullable|string|in:'.strtolower(implode(',',$gender)),
            'duration'=>'string|nullable',
            'price'=>'string|nullable',
            'price_type'=>'required|string|in:'.strtolower(implode(',',$price_type)),
            'price_name'=>'nullable|string',
            'tax'=>'nullable|int'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        $product = Product::find($request->product_id);
        $service = Service::find($product->service_id);
        if ($this->user->can('interact', [$product, $service]))
        {
            $product->name = $request->input('name', $product->name);
            $product->duration = $request->input('duration', $product->duration);
            $product->price = $request->input('price', $product->price);
            $product->description = $request->input('description', $product->description);
            $product->gender = $request->input('gender', $product->gender);
            $product->price_type = $request->input('price_type', $product->pice_type);
            $product->price_name = $request->input('price_name', $product->pice_name);
            $product->tax = $request->input('tax', $product->tax);

            $product->save();

            return response([
                'status'=>true,
                'message'=>'Product edited',
                'data'=>[]
            ]);
        }
        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }

    public function deleteProduct(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            //'service_id' => 'required|integer|exists:services,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $product = Product::find($request->product_id);
        $services = Service::find($product->service_id);
        if ($this->user->can('interact', [$product, $services]))
        {
            //todo: must not have an open order
            $product->delete();

            return response([
                'status'=>true,
                'message'=>'Product deleted',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }

    public function addProductImage(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'file'=>'required|array',
            'file.*' => 'required_with:file|mimes:jpeg,jpg,png',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        $product = Product::find($request->product_id);
        $services = Service::find($product->service_id);
        if ($this->user->can('interact', [$product, $services]))
        {
            if($request->file){
                foreach ($request->file as $file)
                {
                    $message =  $file->store('public/attachments/products');
                    Resource::create([
                        'path'=>$message,
                        'resourceable_id'=>$request->input('product_id'),
                        'resourceable_type'=>'App\Models\Product'
                    ]);
                }
            }
            return response([
                'status'=>true,
                'message'=>'Product image added',
                'data'=>[]
            ]);
        }
        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }

    public function deleteProductImage(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'image_id' => 'required|integer|exists:resources,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        $product = Product::find($request->product_id);
        $services = Service::find($product->service_id);
        if ($this->user->can('interact', [$product, $services]))
        {
            //todo: unlink the image
            Resource::destroy($request->input('image_id'));

            return response([
                'status'=>true,
                'message'=>'Product image deleted',
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
