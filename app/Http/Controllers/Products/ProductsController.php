<?php

namespace App\Http\Controllers\Products;

use App\Classes\ApiError;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductsController extends Controller
{
    public function addProduct(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'name' => 'required|unique:products,name',
            'description' => 'required|max:250',
            'category_id' => 'required|integer|exists:products_category,id',
            'average_price' => 'required|numeric',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $cat_id = $request->input('category_id');

        $prod_cat = ProductCategory::where('id',$cat_id);

        if(!$prod_cat->exists()){

            $err = new ApiError(341,
                NULL,
                'Выберите категорию товара',
                'Категория товара не найдена');
            return $err->json();

        }

        $prod = new Product;

        $prod->name = $request->input('name');
        $prod->description = $request->input('description');
        $prod->category_id = $request->input('category_id');
        $prod->average_price = $request->input('average_price');

        try{

        $prod->save();

            }
            catch (QueryException $ex){
                $err = new ApiError(310);
                return $err->json();
            }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getProductsList(Request $request)
    {

        $products_bld = Product::where('is_deleted',0);

        if($request->exists('category_id') && $request->filled('category_id')){

            $products_bld = $products_bld
                ->where('category_id',$request->input('category_id'));

        }

        if(!$products_bld->exists()){
            $err = new ApiError(341,
                NULL,
                'Товары не найдены',
                'Товары не найдены');
            return $err->json();
        }

        $products = $products_bld
            ->cursor();

        $products_arr = [];

        foreach ($products as $product){

            $products_in_arr = [];

            //Получаем изображения
            $img_arr = [];

            $imgs = $product->images;

            if(isset($imgs)){

                foreach ($imgs as $img){

                    $img_in_arr = [];

                    $img_in_arr['img_id'] =  $img->id ;

                    $img_in_arr['img_href'] =  $img->href;

                    array_push($img_arr,$img_in_arr);

                }

                $products_in_arr['images'] = $img_arr;

            }

            $products_in_arr['name'] =  $product->name;

            $products_in_arr['id'] = $product->id;

            $products_in_arr['description'] = $product->description;

            $products_in_arr['category_id'] = $product->category_id;

            $products_in_arr['average_price'] = $product->average_price;

            array_push($products_arr,$products_in_arr);

        }

        return response()->json([

            'response' => $products_arr,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function editProduct(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',

            'name' => 'required_without_all:description,category_id,average_price|
            unique:products,name',

            'description' => 'required_without_all:name,category_id,average_price|max:250',

            'category_id' => 'required_without_all:description,name,average_price|
            integer|exists:products_category,id',

            'average_price' => 'required_without_all:description,category_id,name|numeric',
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

        if($request->has('name'))
        $product->name = $request->input('name');

        if($request->has('description'))
        $product->description = $request->input('description');

        if($request->has('category_id'))
            $product->category_id = $request->input('category_id');

        if($request->has('average_price'))
            $product->average_price = $request->input('average_price');

        try{
            $product->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteProduct(Request $request)
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

        $product->is_deleted = 1;

        try{
            $product->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getProduct(Request $request)
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

        $resp = [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category_id' => $product->category_id,
            'average_price' => $product->average_price,
        ];

        //Получаем изображения
        $img_arr = [];

        $imgs = $product->images;

        if(isset($imgs)){

            foreach ($imgs as $img){

                $img_in_arr = [];

                $img_in_arr['img_id'] =  $img->id ;

                $img_in_arr['img_href'] =  $img->href;

                array_push($img_arr,$img_in_arr);

            }

            $resp['images'] = $img_arr;

        }

        return response()->json([

            'response' => $resp,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }
}
