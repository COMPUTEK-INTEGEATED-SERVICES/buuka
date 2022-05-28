<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\CategoryRelation;
use App\Models\Escrow;
use App\Models\Resource;
use App\Models\Staff;
use App\Models\State;
use App\Models\Vendor;
use App\Models\VendorImages;
use App\Models\Wallet;
use App\Notifications\Vendor\VendorCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
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

    public function becomeAVendor(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'vendor_package_id' => 'required|int|exists:vendor_packages,id',
            'business_name' => 'required|string|unique:vendors',
            'description' => 'required|string',
            'country' => 'nullable|int|exists:countries,id',
            'state' => 'nullable|int|exists:states,id',
            'city' => 'nullable|int|exists:cities,id',
            'address' => 'required|string',
            'week_start'=>'required|int|exists:weeks,id',
            'week_end'=>'required|int|exists:weeks,id',
            'website'=>'nullable|string|url',
            'facebook'=>'nullable|string|url',
            'instagram'=>'nullable|string|url',
            'file'=>'nullable|array',
            'file.*' => 'required_with:file|mimes:jpeg,jpg,png',
            'category'=>'required|array',
            'category.*'=>'int|exists:parent_categories,id',
            //'latitude'=>'required|nullable',
            //'longitude'=>'required|nullable'
        ]);

        if($v->fails()){
            return $this->validationErrorResponse($v->errors());
        }

        try {
            $vendor = Vendor::where('user_id', $this->user->id)->first();
            if ($vendor){
                return $this->successResponse([], 'You have a business account already!');
            }

            $vendor = Vendor::create([
                'vendor_package_id' => $request->input('vendor_package_id'),
                'user_id'=>$this->user->id,
                'business_name'=>$request->input('business_name'),
                'description'=>$request->input('description'),
                'country_id'=>$request->input('country'),
                'state_id'=>$request->input('state'),
                'city_id'=>$request->input('city'),
                'address'=>$request->input('address'),
                'week_start'=>$request->input('week_start'),
                'week_end'=>$request->input('week_end'),
                'latitude'=>$request->input('latitude'),
                'longitude'=>$request->input('longitude'),
                'socials'=>json_encode([
                    'website'=>$request->input('website'),
                    'facebook'=>$request->input('facebook'),
                    'instagram'=>$request->input('instagram'),
                ]),
            ]);

            foreach ($request->category as $c)
            {
                CategoryRelation::create([
                    'relateable_id'=>$vendor->id,
                    'relateable_type'=>'AppModelsVendor',
                    'category_id'=>$c
                ]);
            }

            //store the images
            if($request->file){
                //upload file
                foreach ($request->file('file') as $file)
                {
                    $message =  $file->store('public/images/vendor/attachments');
                    $type = $file->getMimeType();

                    Resource::create([
                        'path'=>$message,
                        'resourceable_id'=>$vendor->id,
                        'resourceable_type'=>'AppModelsVendor'
                    ]);
                }
            }

            //create vendor wallet and escrow account
            Escrow::create([
                'escrowable_id'=>$vendor->id,
                'escrowable_type'=>'AppModelsVendor',
            ]);

            Wallet::create([
                'walletable_id'=>$vendor->id,
                'walletable_type'=>'AppModelsVendor'
            ]);

            //you are your number one staff
            Staff::create([
                'vendor_id'=>$vendor->id,
                'user_id'=>$this->user->id,
                'confirm_staff_request'=>1,
            ]);

            try {
                $this->user->notify( new VendorCreatedNotification());
            }catch (\Throwable $throwable){
                throw new \Exception($throwable->getMessage());
            }
            return $this->successResponse(['vendor'=>$vendor]);
        }catch (\Throwable $throwable){
            report($throwable);
            return $this->errorResponse();
        }
    }

    public function editVendorDetails(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'vendor_id'=> 'required|int|exists:vendors,id',
            'business_name' => 'nullable|string|unique:vendors',
            'description' => 'nullable|string',
            'country' => 'nullable|int|exists:countries,id',
            'state' => 'nullable|int|exists:states,id',
            'city' => 'nullable|int|exists:cities,id',
            'address' => 'nullable|string',
            'week_start'=>'nullable|int',
            'week_end'=>'nullable|int',
            'website'=>'nullable|string|url',
            'facebook'=>'nullable|string|url',
            'instagram'=>'nullable|string|url',
            'file'=>'nullable|array',
            'longitude'=>'nullable|string',
            'latitude'=>'nullable|string',
            'file.*' => 'required_with:file|mimes:jpeg,jpg,png',
        ]);

        if($v->fails()){
            return $this->validationErrorResponse($v->errors());
        }

        try {
            $vendor = Vendor::find($request->input('vendor_id'));
            if ($this->user->can('interact', $vendor))
            {
                $vendor->business_name = $request->input('business_name', $vendor->business_name);
                $vendor->description = $request->input('description', $vendor->description);
                $vendor->country_id = $request->input('country', $vendor->country_id);
                $vendor->state_id = $request->input('state', $vendor->state_id);
                $vendor->city_id = $request->input('city', $vendor->city_id);
                $vendor->address = $request->input('address', $vendor->address);
                $vendor->week_start = $request->input('week_start', $vendor->week_start);
                $vendor->week_end = $request->input('week_end', $vendor->week_end);
                $vendor->longitude = $request->input('longitude', $vendor->longitude);
                $vendor->latitude = $request->input('latitude', $vendor->latitude);
                $socials = json_decode($request->socials);
                $vendor->socials = json_encode([
                    'website'=>$request->input('website', $socials['website']),
                    'facebook'=>$request->input('facebook', $socials['facebook']),
                    'instagram'=>$request->input('instagram', $socials['instagram']),
                ]);
                $vendor->save();

                if($request->file){
                    foreach ($request->file('file') as $file)
                    {
                        $message =  $file->store('public/images/vendor/attachments');
                        Resource::create([
                            'path'=>$message,
                            'resourceable_id'=>$vendor->id,
                            'resourceable_type'=>'AppModelsVendor'
                        ]);
                    }
                }

                return $this->successResponse(['vendor'=>$vendor], 'Vendor updated');
            }
            return $this->errorResponse('Access denied');
        }catch (\Throwable $throwable){
            report($throwable);
            return $this->errorResponse([], 'Sorry an error occurred');
        }
    }
}
