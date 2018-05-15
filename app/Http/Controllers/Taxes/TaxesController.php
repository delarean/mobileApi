<?php

namespace App\Http\Controllers\Taxes;

use App\Classes\ApiError;
use App\Models\Tax;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TaxesController extends Controller
{

    public function addTax(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:taxes_type,name',
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

        $tax = new Tax;

        $tax->name = $request->input('name');
        $tax->value = $request->input('value');

        try{
            $tax->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }

    public function editTax(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'taxes_id' => 'required|exists:taxes_type,id',
            'name' => 'required_without:value|unique:taxes_type,name',
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

        $tax = Tax::find($request->input('taxes_id'));

        if($request->has('name'))
            $tax->name = $request->input('name');

        if($request->has('value'))
            $tax->value = $request->input('value');

        try{
            $tax->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteTax(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'taxes_id' => 'required|exists:taxes_type,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $tax = Tax::find($request->input('taxes_id'));

        $tax->is_deleted = 1;

        try{
            $tax->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getTaxesList()
    {

        $response = [];

        try{
            $taxes = Tax::where('is_deleted',0)
                ->get(['id','name','value']);
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        if(isset($taxes)){

            foreach ($taxes as $tax){

                $tax_in_arr = [];

                $tax_in_arr['id'] = $tax->id;
                $tax_in_arr['name'] = $tax->name;
                $tax_in_arr['value'] = $tax->value;

                array_push($response,$tax_in_arr);

            }


        }

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getTax(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'taxes_id' => 'required|exists:taxes_type,id',
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
            $tax = Tax::find($request->input('taxes_id'));
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        $response = [];

        if(isset($tax)){
            $response['id'] = $tax->id;
            $response['name'] = $tax->name;
            $response['value'] = $tax->value;
        }

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}
