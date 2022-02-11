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
        $gender = ['male', 'female'];
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
            'file' => 'nullable|mimes:jpeg,jpg,png,gif,pdf',
            'staff'=>'nullable|array'
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

            if ($request->staff && !empty($request->staff))
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
        ]);
    }

    public function editProduct(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'service_id' => 'required|integer|exists:services,id',
            'name'=>'nullable|string',
            'duration'=>'string|nullable',
            'amount'=>'string|nullable',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        $product = Product::find($request->product_id);
        $service = Service::find($request->service_id);
        if ($this->user->can('interact', $service))
        {
            $product->name = $request->input('name', $product->name);
            $product->duration = $request->input('duration', $product->duration);
            $product->amount = $request->input('amount', $product->amount);

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
        ]);
    }

    public function deleteProduct(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'service_id' => 'required|integer|exists:services,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $product = Product::find($request->product_id);
        $services = Service::find($request->service_id);
        if ($this->user->can('interact', $product, $services))
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
        ]);
    }
}
