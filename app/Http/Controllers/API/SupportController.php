<?php


namespace App\Http\Controllers\API;


use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Service;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportController
{
    public function getCountries(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => '',
            'data' => [
                'countries'=>Country::all()
            ]
        ]);
    }

    public function getStates(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'country_id' => 'required|integer|exists:countries,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => '',
            'data' => [
                'states'=>State::where('country_id', $request->country_id)->
                    where('status', 1)->get()
            ]
        ]);
    }

    public function getCities(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'state_id' => 'required|integer|exists:states,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => '',
            'data' => [
                'cities'=>City::where('state_id', $request->state_id)->
                where('status', 1)->get()
            ]
        ]);
    }

    public function addCountry(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'name'=>'required|string',
            'initial'=>'required|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $user = auth()->guard('api')->user();
        if ($user->can('perform_admin_task', Country::class))
        {
            $country = Country::create([
                'name'=>$request->input('name'),
                'initial'=>$request->input('initial'),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Country added',
                'data' => ['country'=>$country]
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Access denied',
            'data' => []
        ]);
    }

    public function addStates(Request $request): \Illuminate\Http\JsonResponse
    {
        //todo: adapt to take more states at a time
        $v = Validator::make( $request->all(), [
            'country_id' => 'required|integer|exists:countries,id',
            'names'=>'required|array',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $user = auth()->guard('api')->user();
        if ($user->can('perform_admin_task', State::class))
        {
            $result = [];
            foreach ($request->names as $name)
            {
                $state = State::create([
                    'country_id'=>$request->input('country_id'),
                    'name'=>$name,
                ]);

                $result[] = $state;
            }

            return response()->json([
                'status' => true,
                'message' => 'State added',
                'data' => ['states'=>$result]
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Access denied',
            'data' => []
        ]);
    }

    public function addCities(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'state_id' => 'required|integer|exists:states,id',
            'names'=>'required|array',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }
        $user = auth()->guard('api')->user();
        if ($user->can('perform_admin_task', City::class))
        {
            $result = [];
            foreach ($request->names as $name)
            {
                $city = City::create([
                    'state_id'=>$request->input('state_id'),
                    'name'=>$name,
                ]);

                $result[] = $city;
            }

            return response()->json([
                'status' => true,
                'message' => 'Cities added',
                'data' => ['city'=>$result]
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Access denied',
            'data' => []
        ]);
    }

    public function editCountry(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'country_id' => 'required|integer|exists:countries,id',
            'name'=>'nullable|string',
            'initial'=>'nullable|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $user = auth()->guard('api')->user();
        if ($user->can('perform_admin_task', Country::class))
        {
            $country = Country::find($request->coutry_id);
            $country->name = $request->input('name', $country->name);
            $country->initial = $request->input('name', $country->initial);
            $country->save();
            return response()->json([
                'status' => true,
                'message' => 'Country edited',
                'data' => ['country'=>$country]
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Access denied',
            'data' => []
        ], 403);
    }

    public function editState(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'state_id' => 'required|integer|exists:states,id',
            'name'=>'nullable|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $user = auth()->guard('api')->user();
        if ($user->can('perform_admin_task', State::class))
        {
            $state = State::find($request->state_id);
            $state->name = $request->input('name', $state->name);
            $state->save();
            return response()->json([
                'status' => true,
                'message' => 'State edited',
                'data' => ['state'=>$state]
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Access denied',
            'data' => []
        ], 403);
    }

    public function editCity(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'city_id' => 'required|integer|exists:cities,id',
            'name'=>'nullable|string',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $user = auth()->guard('api')->user();
        if ($user->can('perform_admin_task', City::class))
        {
            $city = City::find($request->city_id);
            $city->name = $request->input('name', $city->name);
            $city->save();
            return response()->json([
                'status' => true,
                'message' => 'City edited',
                'data' => ['city'=>$city]
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Access denied',
            'data' => []
        ], 403);
    }

    public function deleteCountry(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'country_id' => 'required|integer|exists:countries,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $user = auth()->guard('api')->user();
        if ($user->can('perform_admin_task', Country::class))
        {
            //this endpoint is dangerous to use in production as it can break a lot
            //of things so be sure not to use in production

            $country = Country::find($request->country_id);
            //first step will be to delete all states with the country id
            $states = State::where('country_id', $country->id)->get();
            foreach ($states as $state)
            {
                //check if a city or cities exist
                $cities = City::where('state_id', $state->id)->get();
                foreach ($cities as $city)
                {
                    if (!empty($city))
                    {
                        City::find($city->id)->delete();
                    }
                }

                //cities for this state is done deleting lets delete the state
                State::find($state->id)->delete();
            }
            //then delete the country
            $country->delete();

            return response()->json([
                'status' => true,
                'message' => "This country, it's states and cities deleted successfully",
                'data' => []
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Access denied',
            'data' => []
        ], 403);
    }

    public function deleteState(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'state_id' => 'required|integer|exists:states,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $user = auth()->guard('api')->user();
        if ($user->can('perform_admin_task', State::class))
        {
            //this endpoint is dangerous to use in production as it can break a lot
            //of things so be sure not to use in production

            $state = State::find($request->state_id);
            $cities = City::where('country_id', $state->id)->get();
            foreach ($cities as $city)
            {
                //lets delete the city
                City::find($city->id)->delete();
            }
            //then delete the country
            $state->delete();

            return response()->json([
                'status' => true,
                'message' => "This state and it's cities deleted successfully",
                'data' => []
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Access denied',
            'data' => []
        ], 403);
    }

    public function deleteCity(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = Validator::make( $request->all(), [
            'city_id' => 'required|integer|exists:cities,id',
        ]);

        if($v->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'data' => $v->errors()
            ], 422);
        }

        $user = auth()->guard('api')->user();
        if ($user->can('perform_admin_task', City::class))
        {
            //this endpoint is dangerous to use in production as it can break a lot
            //of things so be sure not to use in production

            $city = State::find($request->city_id);
            $city->delete();

            return response()->json([
                'status' => true,
                'message' => "This city deleted successfully",
                'data' => []
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Access denied',
            'data' => []
        ], 403);
    }

    public function getAllCategories()
    {
        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'category'=>Category::all()
            ]
        ]);
    }

    public function getServices(Request $request)
    {
        $services = Service::with(['images', 'products', 'category'])
            ->where('status', 1)
            ->where(function($query) use ($request) {
                if ($request->input('query') != null)
                {
                    $query->where('name', 'Like', '%' . $request->input('query') . '%');
                }
            })
            ->latest()->paginate(10);

        return response([
            'status'=>true,
            'message'=>'',
            'data'=>[
                'services'=>$services
            ]
        ]);
    }

    public function getService(Request $request)
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

        $service = Service::with(['images', 'products', 'category'])->first();

        return response([
            'status'=>true,
            'message'=>'Chat sent',
            'data'=>[
                'user'=>$this->user,
                'service'=>$service
            ]
        ]);
    }
}
