<?php

namespace App\Http\Controllers\Sales;

use App\Classes\ApiError;
use App\Models\Sale;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PhotosController extends Controller
{

    public function addSalesPhotos(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|integer|exists:sales,id',
            'sale_images' => 'required|array',
            'sale_images.*' => 'required|integer|exists:images,id',
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

        $sale_imgs_ids = $request->input('sale_images');

        try{
            DB::transaction(function () use ($sale_imgs_ids,$sale){
                foreach ($sale_imgs_ids as $sale_img_id){

                    $sale->images()->attach($sale_img_id);

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

    public function deleteSalesPhotos(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|integer|exists:sales,id',
            'sale_images' => 'required|array',
            'sale_images.*' => 'required|integer|exists:images,id',
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

        $sale_imgs_ids = $request->input('sale_images');

        try{
            DB::transaction(function () use ($sale_imgs_ids,$sale){
                foreach ($sale_imgs_ids as $sale_img_id){

                    $sale->images()->detach($sale_img_id);

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
