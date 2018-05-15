<?php

namespace App\Http\Controllers\Products;

use App\Classes\ApiError;
use App\Models\Character;
use App\Models\Product;
use App\Models\ProductCharacter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductsCharacterController extends Controller
{


    public function addProductsCharacter(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'character_id' => 'required|integer|exists:characters,id',
            'quantity' => 'required|numeric',
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

        $character_id = Character::find($request->input('character_id'))->id;

        $quantity = $request->input('quantity');

        try {

            $product->characters()->attach($character_id, ['quantity' => $quantity]);

        }
        catch (QueryException $ex){
            $err = new ApiError(310);

            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteProductsCharacter(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'character_id' => 'required|integer|exists:characters,id',
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

        $character_id = Character::find($request->input('character_id'))->id;

        $quantity = $request->input('quantity');

        try {

            $product->characters()->detach($character_id);

        }
        catch (QueryException $ex){
            $err = new ApiError(310);

            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getProductsCharactersList(Request $request)
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

        $productModel = Product::find($request->input('product_id'));

        $characters = $productModel->characters()
            ->get(['characters.id','characters.name','characters.unit','characters.description'])
            ->toArray();

        foreach ($characters as &$character){

            $prod_character = ProductCharacter::where('character_id',$character['id'])
                ->where('product_id',$productModel->id)
                ->first();

            $character['quantity'] = $prod_character->quantity;

            unset($character['pivot']);

        }

        return response()->json([

            'response' => $characters,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}
