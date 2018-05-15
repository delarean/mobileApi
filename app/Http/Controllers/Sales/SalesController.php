<?php

namespace App\Http\Controllers\Sales;

use App\Classes\ApiError;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SalesController extends Controller
{

    public function addSale(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:sales,name',
            'start_date' => 'required|integer',
            'end_date' => 'required|integer',
            'description' => 'required|max:255',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $sale = new Sale;

        $sale->name = $request->input('name');
        $sale->start_date = date("Y-m-d H:i:s",$request->input('start_date'));
        $sale->end_date = date("Y-m-d H:i:s",$request->input('end_date'));
        $sale->description = $request->input('description');

        $user_id = $request->input('user_id');

        //$user = User::find($user_id);

        $sale->user_id = $user_id;

        try{
            $sale->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function editSale(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'sales_id' => 'required|exists:sales,id',

            'name' => 'required_without_all:start_date,end_date,description,user_id
            |unique:sales,name',

            'start_date' => 'required_without_all:name,end_date,description,user_id
            |integer',

            'end_date' => 'required_without_all:name,start_date,description,user_id|integer',
            'description' => 'required_without_all:name,start_date,end_date,user_id|max:255',
            'user_id' => 'required_without_all:name,end_date,description,start_date
            |integer|exists:users,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $sale = Sale::find($request->input('sales_id'));

        if($request->has('name'))
        $sale->name = $request->input('name');

        if($request->has('start_date'))
        $sale->start_date = date("Y-m-d H:i:s",$request->input('start_date'));

        if($request->has('end_date'))
        $sale->end_date = date("Y-m-d H:i:s",$request->input('end_date'));

        if($request->has('description'))
        $sale->description = $request->input('description');

        if($request->has('user_id'))
        $sale->user_id = $request->input('user_id');

        try{
            $sale->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteSale(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'sales_id' => 'required|exists:sales,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $sale = Sale::find($request->input('sales_id'));

        $sale->is_deleted = 1;

        try{
            $sale->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getSale(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'sales_id' => 'required|exists:sales,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $sale = Sale::find($request->input('sales_id'));

        $resp = [
          'id' => $sale->id,
           'name' =>  $sale->name,
            'start_date' =>  strtotime($sale->start_date),
            'end_date' =>  strtotime($sale->end_date),
            'description' =>  $sale->description,
            'created_at' =>  strtotime($sale->created_at),
            'cities_ids' => [],
            'images_ids' => [],
            'initiator_name' => NULL,
        ];

        $user_bld = $sale->user();
        if($user_bld->exists()){
            $user = $user_bld->first();
            $user_opt_bld = $user->userOpt();
            if($user_opt_bld->exists()){

                $user_opt = $user_opt_bld->first();
                $resp['initiator_name'] = $user_opt->short_name;

            }
        }

        $cities = $sale->cities->toArray();

        foreach ($cities as $city){

            $city_in_arr = [
                'id' => $city['id'],
                'name' => $city['name'],
            ];

            array_push($resp['cities_ids'],$city_in_arr);
        }

        $images = $sale->images->toArray();

        foreach ($images as $image){
            $img_in_arr = [
                'id' => $image['id'],
                'href' => $image['href'],
            ];

            array_push($resp['images_ids'],$img_in_arr);
        }


        return response()->json([

            'response' => $resp,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getSalesList()
    {

        $sales = Sale::where('is_deleted',0)
            ->cursor();

        $response = [];

        foreach ($sales as $sale) {
            $resp = [
                'id' => $sale->id,
                'name' => $sale->name,
                'start_date' => strtotime($sale->start_date),
                'end_date' => strtotime($sale->end_date),
                'description' => $sale->description,
                'created_at' => strtotime($sale->created_at),
                'cities_ids' => [],
                'images_ids' => [],
                'initiator_name' => NULL,
            ];

            $user_bld = $sale->user();
            if($user_bld->exists()){
                $user = $user_bld->first();
                $user_opt_bld = $user->userOpt();
                if($user_opt_bld->exists()){
                    $user_opt = $user_opt_bld->first();
                    $resp['initiator_name'] = $user_opt->short_name;
                }
            }

            $cities = $sale->cities->toArray();

            if(isset($cities)){
                foreach ($cities as $city) {
                    $city_in_arr = [
                        'id' => $city['id'],
                        'name' => $city['name'],
                    ];

                    array_push($resp['cities_ids'],$city_in_arr);
                }
            }

            $images = $sale->images->toArray();

            if(isset($images)){
                foreach ($images as $image) {
                    $img_in_arr = [
                        'id' => $image['id'],
                        'href' => $image['href'],
                    ];

                    array_push($resp['images_ids'],$img_in_arr);
                }
            }

            array_push($response,$resp);
        }

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

