<?php

namespace App\Http\Controllers\News;

use App\Classes\ApiError;
use App\Models\News;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NewsController extends Controller
{
    public function addNews(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:news,name',
            'description' => 'required|max:255',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $news = new News;

        $news->name = $request->input('name');
        $news->description = $request->input('description');

        try{
            $news->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function editNews(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required_without_all:description|unique:news,name',
            'description' => 'required_without_all:name|max:255',
            'news_id' => 'required|exists:news,id'
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $news = News::find($request->input('news_id'));

        if($request->has('name'))
        $news->name = $request->input('name');

        if($request->has('description'))
        $news->description = $request->input('description');

        try{
            $news->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteNews(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'news_id' => 'required|exists:news,id'
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $news = News::where('is_deleted',0)
            ->find($request->input('news_id'));

        if(!isset($news)){
            $err = new ApiError(341,
                NULL,
                'Новость уже удалена',
                'Новость уже удалена');
            return $err->json();
        }

        $news->is_deleted = 1;

        try{
            $news->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getOneNews(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'news_id' => 'required|exists:news,id'
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $news = News::find($request->input('news_id'));

        $response = [
          'name' => $news->name,
          'description' => $news->description,
            'created_at' => strtotime($news->created_at),
            'images' => [],
        ];

        $news_imgs = $news->images;

        if(isset($news_imgs)){

            foreach ($news_imgs as $news_img){

                $news_in_arr = [];

                $news_in_arr['image_id'] = $news_img->id;
                $news_in_arr['image_href'] = $news_img->href;

                array_push($response['images'],$news_in_arr);

            }
        }

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getNewsList()
    {

        $news = News::where('is_deleted',0)
            ->cursor();

        $response = [];

        foreach ($news as $one_news){

            $resp = [];
            $resp['id'] = $one_news->id;
            $resp['name'] = $one_news->name;
            $resp['images'] = [];
            $resp['description'] = $one_news->description;
            $resp['created_at'] = strtotime($one_news->created_at);

            $news_imgs = $one_news->images;

            if(isset($news_imgs)){

                foreach ($news_imgs as $news_img){

                    $news_in_arr = [];

                    $news_in_arr['image_id'] = $news_img->id;
                    $news_in_arr['image_href'] = $news_img->href;

                    array_push($resp['images'],$news_in_arr);

                }
            }

            array_push($response,$resp);
        }

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function addNewsPhotos(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'news_id' => 'required|integer|exists:news,id',
            'news_images' => 'required|array',
            'news_images.*' => 'required|integer|exists:images,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $news = News::find($request->input('news_id'));

        $news_imgs_ids = $request->input('news_images');

        try{
            DB::transaction(function () use ($news_imgs_ids,$news){
                foreach ($news_imgs_ids as $news_img_id){

                    $news->images()->attach($news_img_id);

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

    public function deleteNewsPhotos(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'news_id' => 'required|integer|exists:news,id',
            'news_images' => 'required|array',
            'news_images.*' => 'required|integer|exists:images,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $news = News::find($request->input('news_id'));

        $news_imgs_ids = $request->input('news_images');

        try{
            DB::transaction(function () use ($news_imgs_ids,$news){
                foreach ($news_imgs_ids as $news_img_id){

                    $news->images()->detach($news_img_id);

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
