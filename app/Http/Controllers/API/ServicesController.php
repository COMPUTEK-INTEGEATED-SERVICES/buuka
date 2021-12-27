<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Action\AddressAction;
use App\Http\Controllers\Action\ValidationAction;
use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Models\ServiceImages;
use App\Models\ServiceLocation;
use App\Models\ServicePrices;
use App\Models\Services;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class ServicesController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;
    /**
     * @var AddressAction
     */
    private $__address;

    public function __construct()
    {
        $this->user = auth()->guard('api')->user();
        $this->__address = new AddressAction();
    }

    public function addService(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'name'=>'required|string',
            'description'=>'string|required',
            'category_id'=>'required',
            'country'=>'required|string',
            'state'=>'required|string',
            'city'=>'string'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => 'false',
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        try {
            //store the service
            $service = Services::create([
                'name'=>$request->input('name'),
                'description'=>$request->input('description'),
            ]);

            //store service categories
            foreach ($request->input('category') as $category)
            {
                ServiceCategory::create([
                    'service_id'=>$service->id,
                    'category'=>$category
                ]);
            }

            //store the service location
            ServiceLocation::create([
                'service_id'=>$service->id,
                'country'=>$request->input('country'),
                'state'=>$request->input('state'),
                'city'=>$request->input('city')
            ]);

            //store the images
            if($request->file){
                //upload file
                foreach ($request->file('file') as $file)
                {
                    $message =  $file->store('public/images/service/attachments');
                    $type = $file->getMimeType();

                    ServiceImages::create([
                        'service_id'=>$service->id,
                        'image'=>$message,
                        'type'=>$type
                    ]);
                }
            }

            //store the prices
            if ($request->input('prices')){
                foreach ($request->input('prices') as $price){
                    ServicePrices::create([
                        'name'=>$price->name,
                        'service_id'=>$service->id,
                        'price'=>$price->amount
                    ]);
                }
            }
            return response([
                'status'=>true,
                'message'=>'Service created',
                'data'=>[]
            ]);

        }catch (\Throwable $throwable)
        {
            report($throwable);
        }
        return response([
            'status'=>false,
            'message'=>'An error occurred',
            'data'=>[]
        ]);
    }

    public function getService(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => 'false',
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $service = Services::with(['images', 'prices', 'category', 'review'])->first();

        return response([
            'status'=>true,
            'message'=>'Chat sent',
            'data'=>[
                'user'=>$this->user,
                'service'=>$service
            ]
        ]);
    }

    public function editService(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'name'=>'nullable|string',
            'description'=>'string|nullable',
            'category_id'=>'nullable',
            'country'=>'nullable|string',
            'state'=>'nullable|string',
            'city'=>'string'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => 'false',
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $service = Services::find($request->input('service_id'));
        $service->name = $request->input('name', $service->name);
        $service->description = $request->input('description', $service->description);
        $service->save();

        $category = ServiceCategory::where('service_id', $request->input('service_id'));
        $category->category_id = $request->input('category_id', $category->category_id);
        $category->save();

        $location = ServiceLocation::where('service_id', $request->input('service_id'));
        $location->country = $request->input('country', $location->country);
        $location->state = $request->input('state', $location->state);
        $location->city = $request->input('city', $location->city);
        $location->save();

        return response([
            'status'=>true,
            'message'=>'Service updated',
            'data'=>[]
        ]);
    }

    public function deleteServiceImage(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'image_id' => 'required|integer|exists:service_images,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => 'false',
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        if (Services::userOwnsService($this->user->id, $request->input('service_id')))
        {
            ServiceImages::destroy($request->input('image_id'));

            return response([
                'status'=>true,
                'message'=>'Service image deleted',
                'data'=>[]
            ]);
        }
        return response([
            'status'=>false,
            'message'=>'Not authorized',
            'data'=>[]
        ], 401);
    }

    public function deleteService(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => 'false',
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        if (Services::userOwnsService($this->user->id, $request->input('service_id')))
        {
            //make sure that no book is on for this service
            //unlink the images
            $images = ServiceImages::where('service_id', $request->input('service_id'))->get();
            foreach ($images as $image)
            {
                try {
                    unlink($image->image);
                }catch (\Throwable $throwable)
                {
                    report($throwable);
                }
            }
            Services::where('id', $request->input('service_id'))->delete();
            ServiceImages::where('service_id', $request->input('service_id'))->delete();
            ServiceCategory::where('service_id', $request->input('service_id'))->delete();
            ServicePrices::where('service_id', $request->input('service_id'))->delete();
            ServiceLocation::where('service_id', $request->input('service_id'))->delete();

            return response([
                'status'=>true,
                'message'=>'Service deleted',
                'data'=>[]
            ]);
        }
        return response([
            'status'=>false,
            'message'=>'Not authorized',
            'data'=>[]
        ], 401);
    }

    public function addServiceImage(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'file' => 'nullable|mimes:jpeg,jpg,png'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => 'false',
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        if (Services::userOwnsService($this->user->id, $request->input('service_id')))
        {
            if($request->file){
                //upload file
                $file =  $request->file('file')->store('public/attachments');

                $type = $request->file('file')->getMimeType();

                ServiceImages::create([
                    'service_id'=>$request->input('service_id'),
                    'image'=>$file,
                    'type'=>$type
                ]);

                return response([
                    'status'=>true,
                    'message'=>'Service image deleted',
                    'data'=>[]
                ]);
            }
        }
        return response([
            'status'=>false,
            'message'=>'Not authorized',
            'data'=>[]
        ], 401);
    }
}
