<?php

namespace App\Http\Controllers\Image;

use App\Classes\ApiError;
use App\Models\Image;
use App\Models\PhoneSalt;
use Illuminate\Support\Facades\Storage;
use \Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ImageController extends Controller
{
    public function uploadImage(Request $request)
    {

        if(!$request->hasFile('image_file')){

            $err = new ApiError(342,
                NULL,
                "Не передан файл",
                "Не передан файл");
            return $err->json();

        }

        $file = $request->file('image_file');

        if(!$file->isValid()){
            $err = new ApiError(343,
                NULL,
                "Файл повреждён ,попробуйте снова",
                "Файл повреждён ,попробуйте снова");
            return $err->json();
        }

        $validator = Validator::make($request->all(), [
            'image_file' => 'image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {

            $err = new ApiError(341,
                NULL,
                "Неверный формат файла.Доступные форматы изображений - jpg,jpeg,png",
                "Неверный формат файла.Доступные форматы изображений - jpg,jpeg,png");
            return $err->json();

        }

        $path = $file->storePublicly('public');

        $image = new Image;

        //$auth_token = $request->input('auth_token');
        /*$user_id = PhoneSalt::where('auth_token',$auth_token)
                            ->first()
                            ->user
                            ->id;*/

        $image->href = Storage::url($path);
        //$image->owner_id = $user_id;
        $image->is_accepted = 2;

        $image->save();

        return response()->json([

            'response' => [

                'image_id' => $image->id,
                'href' => $image->href,

            ],

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteImage(Request $request)
    {

        $required_params = ['image_id'];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }

        $image_id = $request->input('image_id');

        $user = $this->getCurrentUser($request->input('auth_token'));

        $image_bld = Image::where('id',$image_id);

        if(!$image_bld->exists()){

            $err = new ApiError(341,
                NULL,
                "Изображение не найдено",
                "Изображение не найдено");
            return $err->json();

        }

            $image_coll = $image_bld->get(['owner_id','href'])->first();

            if($user->id !== $image_coll->owner_id){

                $err = new ApiError(308,
                    NULL,
                    'Вы не можете удалить это изображение');
                return $err->json();

            }

                $image_bld->first()->delete();
                $href = $image_coll->href;

                $href_parts = explode('/',$href);

                $fileName = $href_parts[2];

                $path = 'public/'.$fileName;

                Storage::delete($path);


        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }
}
