<?php

namespace App\Http\Controllers\Businesses;

use App\Classes\ApiError;
use App\Models\Business;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BusinessesController extends Controller
{

    public function addBusiness(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:bussines_type,name',
            'value' => 'required',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $business = new Business;

        $business->name = $request->input('name');
        $business->value = $request->input('value');

        try{
            $business->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }

    public function editBusiness(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bussines_id' => 'required|exists:bussines_type,id',
            'name' => 'required_without:value|unique:bussines_type,name',
            'value' => 'required_without:name',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $business = Business::find($request->input('bussines_id'));

        if($request->has('name'))
        $business->name = $request->input('name');

        if($request->has('value'))
        $business->value = $request->input('value');

        try{
            $business->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteBusiness(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bussines_id' => 'required|exists:bussines_type,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $business = Business::find($request->input('bussines_id'));

        $business->is_deleted = 1;

        try{
            $business->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getBusinessesList()
    {

        $response = [];

        try{
            $businesses = Business::where('is_deleted',0)
                ->get(['id','name','value']);
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        if(isset($businesses)){

            foreach ($businesses as $business){

                $business_in_arr = [];

                $business_in_arr['id'] = $business->id;
                $business_in_arr['name'] = $business->name;
                $business_in_arr['value'] = $business->value;

                array_push($response,$business_in_arr);

            }


        }

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getBusiness(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bussines_id' => 'required|exists:bussines_type,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        try{
            $business = Business::find($request->input('bussines_id'));
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        $response = [];

        if(isset($business)){
            $response['id'] = $business->id;
            $response['name'] = $business->name;
            $response['value'] = $business->value;
        }

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}
