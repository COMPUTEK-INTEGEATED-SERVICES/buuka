<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Action\AddressAction;
use App\Http\Controllers\Action\ValidationAction;
use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Models\ServiceImages;
use App\Models\ServicePrices;
use App\Models\Services;
use Illuminate\Support\Facades\Request;

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
        ValidationAction::validate($request, [
           'name'=>'required|string',
            'description'=>'string|required',
            'category_id'=>'required'
        ]);

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
                        'price'
                    ]);
                }
            }

        }catch (\Throwable $throwable)
        {
            report($throwable);
        }
    }
}
