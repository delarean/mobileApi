<?php

namespace App\Http\Controllers\Products;

use App\Classes\ApiError;
use App\Models\ProductCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductCategoryController extends Controller
{
    public function addCategory(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:products_category,name',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $prod_cat = new ProductCategory;

        $prod_cat->name = $request->input('name');

        $prod_cat->save();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getCategoriesList()
    {

        $categories = ProductCategory::where('is_deleted',0);

        if(!$categories->exists()){
            $err = new ApiError(341,
                NULL,
                'Нет категорий',
                'Нет категорий');
            return $err->json();
        }

        $categories_arr = $categories->get(['id','name',]);

        $response = [
        [
            'id' => "",
            'name' => "Все товары",
        ]
        ];

        $total_count = 0;

        foreach ($categories_arr as $category){
            $prod_count = $category->products->count();

            $response[] = [
            'id' => $category->id,
            'name' => $category->name,
            'quantity' => $prod_count,
            ];

            $total_count += $prod_count;

        }

        $response[0]['quantity'] = $total_count;

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function editCategory(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer|exists:products_category,id',
            'name' => 'required|unique:products_category,name',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $prod_cat = ProductCategory::find($request->input('category_id'));

        $prod_cat->name = $request->input('name');

        $is_saved = $prod_cat->save();

        if(!$is_saved){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteCategory(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer|exists:products_category,id',
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

        $prod_cat = ProductCategory::find($cat_id);

        $prod_cat->is_deleted = 1;

        try {

            $prod_cat->save();

        }
        catch (QueryException $ex) {
            $err = new ApiError(310);
            return $err->json();
        }


        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }
}
