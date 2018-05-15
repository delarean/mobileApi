<?php

namespace App\Http\Controllers\Sales;

use App\Classes\ApiError;
use App\Models\Sale;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CitiesController extends Controller
{

    public function addSalesCities(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|integer|exists:sales,id',
            'cities' => 'required|array',
            'cities.*' => 'required|integer|exists:cities,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $sale = Sale::find($request->input('sale_id'));

        $sale_cts_ids = $request->input('cities');

        try{
            DB::transaction(function () use ($sale_cts_ids,$sale){
                foreach ($sale_cts_ids as $sale_ct_id){

                    $sale->cities()->attach($sale_ct_id);

                }
            });
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteSalesCities(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|integer|exists:sales,id',
            'cities' => 'required|array',
            'cities.*' => 'required|integer|exists:cities,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $sale = Sale::find($request->input('sale_id'));

        $sale_cts_ids = $request->input('cities');

        try{
            DB::transaction(function () use ($sale_cts_ids,$sale){
                foreach ($sale_cts_ids as $sale_ct_id){

                    $sale->cities()->detach($sale_ct_id);

                }
            });
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);
    }

}
