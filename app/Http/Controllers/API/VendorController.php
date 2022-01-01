<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorImages;
use App\Notifications\Vendor\VendorCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
{
    public function becomeAVendor(Request $request)
    {
        $user = auth()->guard('api')->user();
        $v = Validator::make( $request->all(), [
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
            'instagram'=>'nullable|string|url'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $vendor = Vendor::create([
            'user_id'=>$user->id,
            'business_name'=>$request->input('business_name'),
            'description'=>$request->input('description'),
            'country_id'=>$request->input('country'),
            'state_id'=>$request->input('state'),
            'city_id'=>$request->input('city'),
            'address'=>$request->input('address'),
            'week_start'=>$request->input('week_start'),
            'week_end'=>$request->input('week_end'),
            'socials'=>json_encode([
                'website'=>$request->input('website'),
                'facebook'=>$request->input('facebook'),
                'instagram'=>$request->input('instagram'),
            ]),
        ]);

        //store the images
        if($request->file){
            //upload file
            foreach ($request->file('file') as $file)
            {
                $message =  $file->store('public/images/vendor/attachments');
                $type = $file->getMimeType();

                VendorImages::create([
                    'vendor_id'=>$vendor->id,
                    'image'=>$message,
                    'type'=>$type
                ]);
            }
        }

        try {
            $user->notify( new VendorCreatedNotification());

        }catch (\Throwable $throwable){
            report($throwable);
        }
        return response([
            'status'=>true,
            'message'=>'Vendor account created',
            'data'=>[
                'vendor'=>$vendor
            ]
        ]);
    }

    public function editVendorDetails(Request $request)
    {
        //Todo: this route should be throttled to once in 3hrs and twice a month also
        // should not go through if the vendor has an unfulfilled service/order

        $user = auth()->guard('api')->user();
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
            'instagram'=>'nullable|string|url'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $vendor = Vendor::find($request->input('vendor_id'));
        if ($user->can('edit', $vendor))
        {
            $vendor->business_name = $request->input('business_name', $vendor->business_name);
            $vendor->description = $request->input('description', $vendor->description);
            $vendor->country_id = $request->input('country', $vendor->country_id);
            $vendor->state_id = $request->input('state', $vendor->state_id);
            $vendor->city_id = $request->input('city', $vendor->city_id);
            $vendor->address = $request->input('address', $vendor->address);
            $vendor->week_start = $request->input('week_start', $vendor->week_start);
            $vendor->week_end = $request->input('week_end', $vendor->week_end);
            $socials = json_decode($request->socials);
            $vendor->socials = json_encode([
                'website'=>$request->input('website', $socials['website']),
                'facebook'=>$request->input('facebook', $socials['facebook']),
                'instagram'=>$request->input('instagram', $socials['instagram']),
            ]);
            $request->save();

            return response([
                'status'=>true,
                'message'=>'Vendor updated',
                'data'=>[
                    'vendor'=>$vendor
                ]
            ]);
        }
        return response([
            'status'=>false,
            'message'=>'Access denied',
            'data'=>[]
        ]);
    }
}
