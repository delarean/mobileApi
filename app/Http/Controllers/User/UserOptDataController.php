<?php

namespace App\Http\Controllers\User;

use App\Classes\ApiError;
use App\Models\Image;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserOptDataController extends Controller
{

    public function addUserOptLogo(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'image_id' => 'required|integer|exists:images,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        if($user->orgform !== 2){

            $err = new ApiError(341,
                NULL,
                'Вы не являетесь юридическим лицом',
                "Пользователь не юр. лицо");

            return $err->json();

        }

        $userOpt = $user->userOpt;

        $image_id = $request->input('image_id');

        $userOpt->image_id = $image_id;

        $img = Image::find($image_id);
        $img->owner_id = $user->id;

        try{
            DB::transaction(function () use ($img,$userOpt){

                $img->save();
                $userOpt->save();

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
