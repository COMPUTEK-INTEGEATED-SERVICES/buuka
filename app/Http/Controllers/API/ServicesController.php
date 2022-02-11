<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Action\AddressAction;
use App\Http\Controllers\Action\ValidationAction;
use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\ServiceCategory;
use App\Models\ServiceImages;
use App\Models\ServiceLocation;
use App\Models\ServicePrices;
use App\Models\Service;
use App\Models\Vendor;
use Illuminate\Http\Request;
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
            'category_id'=>'nullable|array',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('interact', Vendor::where('user_id', $this->user->id)->first()))
        {
            //store the service
            $service = Service::create([
                'vendor_id'=>$this->user->id,
                'name'=>$request->input('name'),
                'description'=>$request->input('description'),
            ]);

            //store service categories
            /*foreach ($request->input('category_id') as $category)
            {
                ServiceCategory::create([
                    'service_id'=>$service->id,
                    'category_id'=>$category
                ]);
            }*/

            //store the images
            if($request->file){
                //upload file
                foreach ($request->file('file') as $file)
                {
                    $message =  $file->store('public/images/service/attachments');
                    $type = $file->getMimeType();

                    Resource::create([
                        'path'=>$message,
                        'resourceable_id'=>$service->id,
                        'resourceable_type'=>'App\Models\Service'
                    ]);
                }
            }

            return response([
                'status'=>true,
                'message'=>'Service created',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }

    public function editService(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'name'=>'nullable|string',
            'description'=>'string|nullable',
            'category_id'=>'nullable',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('interact', Vendor::class) && $this->user->can('interact', Service::class))
        {
            //todo: service should not have an open order
            $service = Service::find($request->input('service_id'));
            $service->name = $request->input('name', $service->name);
            $service->description = $request->input('description', $service->description);
            $service->save();

            $category = ServiceCategory::where('service_id', $request->input('service_id'));
            $category->category_id = $request->input('category_id', $category->category_id);
            $category->save();

            return response([
                'status'=>true,
                'message'=>'Service updated',
                'data'=>[]
            ]);
        }
        return response([
            'status'=>true,
            'message'=>'Access denied',
            'data'=>[]
        ], 401);
    }

    public function deleteServiceImage(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'image_id' => 'required|integer|exists:service_images,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        if ($this->user->can('interact', Vendor::class) && $this->user->can('interact', Service::class))
        {
            Resource::destroy($request->input('image_id'));

            return response([
                'status'=>true,
                'message'=>'Service image deleted',
                'data'=>[]
            ]);
        }
        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }

    public function deleteService(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        if ($this->user->can('interact', Vendor::class) && $this->user->can('interact', Service::class))
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
                Resource::find($image->id)->delete();
            }
            Service::find($request->input('service_id'))->delete();
            ServiceCategory::where('service_id', $request->input('service_id'))->delete();

            return response([
                'status'=>true,
                'message'=>'Service deleted',
                'data'=>[]
            ]);
        }
        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }

    public function addServiceImage(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'file' => 'nullable|mimes:jpeg,jpg,png'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        if ($this->user->can('interact', Vendor::class) && $this->user->can('interact', Service::class))
        {
            if($request->file){
                //upload file
                $file =  $request->file('file')->store('public/attachments');

                $type = $request->file('file')->getMimeType();

                Resource::create([
                    'path'=>$file,
                    'resourceable_id'=>$request->input('service_id'),
                    'resourceable_type'=>'App\Models\Service'
                ]);

                return response([
                    'status'=>true,
                    'message'=>'Service image added',
                    'data'=>[]
                ]);
            }
        }
        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ], 403);
    }
}
