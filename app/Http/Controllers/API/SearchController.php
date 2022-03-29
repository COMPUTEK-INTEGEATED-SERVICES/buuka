<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function searchServices(Request $request)
    {
        $services = Service::with(['products', 'vendor'])
            ->where('services.status', 1)
            ->leftJoin('vendors', 'services.vendor_id', '=', 'vendors.id')
            ->where('vendors.status', 1)
            ->leftJoin('products', 'products.service_id', '=', 'services.id')
            ->leftJoin('category_relations', 'category_relations.relateable_id', '=', 'vendors.id')
            ->where(function($query) use ($request) {
                if ($request->input('query') != null)
                {
                    $query->where('services.name', 'Like', '%' . $request->input('q') . '%');
                }
                if ($request->input('category_id') != null)
                {
                    $query->where('category_relations.category_id', $request->input('category_id'));
                }
                if ($request->input('city') != null)
                {
                    $query->where('vendors.city_id', $request->input('city_id'));
                }/*else{
                    $query->leftJoin('vendors', 'vendors.city_id', '=', $this->city);
                }*/
                if ($request->input('state') != null)
                {
                    $query->where('vendors.state_id', $request->input('state_id'));
                }/*else{
                    $query->leftJoin('vendors', 'vendors.state_id', '=', $this->state);
                }*/
                if ($request->input('country') != null)
                {
                    $query->where('vendors.country_id', $request->input('country_id'));
                }/*else{
                    $query->leftJoin('vendors', 'vendors.country_id', '=', $this->country);
                }*/
            })
            ->select('services.*', 'category_relations.category_id')
            ->orderBy('services.created_at', 'desc')
            ->orderBy('services.id', 'desc')
            ->paginate(10);

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'services'=>$services
            ]
        ]);
    }
}
