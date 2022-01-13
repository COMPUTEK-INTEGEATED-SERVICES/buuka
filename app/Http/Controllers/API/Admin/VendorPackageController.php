<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\VendorPackage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VendorPackageController extends Controller
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

    public function createVendorType(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'name' => 'required|string',
            'tax' => 'required|string',
            'commission' => 'required|string',
            'color' => 'required|string'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('perform_admin_task', $this->user)){
            VendorPackage::create([
                'name' => $request->input('name'),
                'tax' => $request->input('tax'),
                'commission' => $request->input('commission'),
                'color' => $request->input('color')
            ]);

            return response([
                'status'=>true,
                'message'=>'Vendor Type Created',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access Denied',
            'data'=>[]
        ], 403);



    }

    public function editVendorType(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'vendor_package_id' => 'required|integer|exists:vendor_packages,id',
            'name' => 'string',
            'tax' => 'string',
            'commission' => 'string',
            'color' => 'string'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('perform_admin_task', $this->user)){
            $vendor_package = VendorPackage::find($request->vendor_package_id);
            $vendor_package->name = $request->input('name', $vendor_package->name );
            $vendor_package->tax = $request->input('tax', $vendor_package->tax );
            $vendor_package->commission = $request->input('commission', $vendor_package->commission );
            $vendor_package->color = $request->input('color', $vendor_package->color );
            $vendor_package->save();

            return response([
                'status'=>true,
                'message'=>'Vendor Type updated',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access Denied',
            'data'=>[]
        ], 403);


    }

    public function enable(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'vendor_package_id' => 'required|integer|exists:vendor_packages,id'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('perform_admin_task', $this->user)){
            $vendor_package = VendorPackage::find($request->vendor_package_id);
            $vendor_package->status = 1;
            $vendor_package->save();

            return response([
                'status'=>true,
                'message'=>'Package Enabled',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access Denied',
            'data'=>[]
        ], 403);



    }

    public function disable(Request $request)
    {
        $v = Validator::make( $request->all(), [
            'vendor_package_id' => 'required|integer|exists:vendor_packages,id'
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        if ($this->user->can('perform_admin_task', $this->user)){
            $vendor_package = VendorPackage::find($request->vendor_package_id);
            $vendor_package->status = 0;
            $vendor_package->save();

            return response([
                'status'=>true,
                'message'=>'Package Disabled',
                'data'=>[]
            ]);
        }

        return response([
            'status'=>false,
            'message'=>'Access Denied',
            'data'=>[]
        ], 403);
    }
}
