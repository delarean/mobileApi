<?php

namespace App\Http\Controllers\Products;

use App\Classes\ApiError;
use App\Models\Image;
use App\Models\ProductImage;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductsImageController extends Controller
{

    public function addProductPhotos(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product_images' => 'required|array',
            'product_images.*' => 'required|integer|exists:images,id',
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

        $images_ids = $request->input('product_images');

        $images_bld = Image::whereIn('id',$images_ids);

        $images_arr = $images_bld
            ->get()
            ->toArray();

        $pr_id = $request->input('product_id');

        try {

            DB::transaction(function () use ($images_arr, $pr_id) {


                foreach ($images_arr as $image) {

                    $pr_img = new ProductImage;

                    $pr_img->image_id = $image['id'];

                    $pr_img->product_id = $pr_id;

                    $pr_img->save();

                }


            });
        } catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteProductPhotos(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'image_id' => 'required|integer|exists:images,id',
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

        $image_id = $request->input('image_id');
        $pr_id = $request->input('product_id');

        $pr_img_bound = ProductImage::where('product_id',$pr_id)
            ->where('image_id',$image_id)
            ->first();

        $is_deleted = $pr_img_bound->delete();

        if(!$is_deleted){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}
