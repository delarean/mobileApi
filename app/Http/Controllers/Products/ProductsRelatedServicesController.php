<?php

namespace App\Http\Controllers\Products;

use App\Classes\ApiError;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductsRelatedServicesController extends Controller
{

    public function addProductRelatedService(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'related_services_id' => 'required|integer|exists:related_services,id',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $product = Product::find($request->input('product_id'));


        try{
        $product->relatedServices()
            ->attach($request->input('related_services_id'));
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }

    public function deleteProductRelatedService(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'related_services_id' => 'required|integer|exists:related_services,id',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $product = Product::find($request->input('product_id'));


        try{
            $product->relatedServices()
                ->detach($request->input('related_services_id'));
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getProductRelatedServicesList(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $product = Product::find($request->input('product_id'));

        $rel_services = $product->relatedServices()
            ->get(['related_services.id','related_services.name','related_services.description'])
            ->toArray();

        foreach ($rel_services as &$rel_service){
            unset($rel_service['pivot']);
        }

        return response()->json([

            'response' => $rel_services,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}
