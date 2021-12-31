<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Service;
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
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'name'=>'required|string',
            'duration'=>'string|required',
            'amount'=>'string|required',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('create', Service::find($request->service_id)))
        {
            $product = Product::create($request->toArray());

            $this->user->notify(new ProductCreatedNotification($product));

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
        if ($this->user->can('interact', $product, $service))
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
