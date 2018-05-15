<?php

namespace App\Http\Controllers\User;

use App\Classes\ApiError;
use App\Models\PhoneSalt;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class UserDataController extends Controller
{

    public function getInfo(Request $request)
    {

        $auth_tok = $request->input('auth_token');

        $phone_salt = PhoneSalt::where('auth_token',$auth_tok)
            ->first();

        $user = $phone_salt->user;

        if(!isset($user)) {
            $err = new ApiError(341,
                NULL,
                "Требуется войти в приложение",
                "Пользователь не найден");
            return $err->json();
        }

                $user_phone = $phone_salt->phone;
                $app_type = $phone_salt->app_type;


            $name = $user->name;
            $surname = $user->surname ?? 0;
            $birthday = $user->birthday;
            $email = $user->email ?? 0;
            $user_type = $user->user_type;
            $orgform = $user->orgform;
            $city_id = $user->city_id;
            $lat = $user->lat ?? 0;
            $lon = $user->lat ?? 0;
            $is_service = $user->is_service ?? 0;
            $is_shop = $user->is_shop ?? 0;

            $reponse = [
                'name' => $name,
                'surname' => $surname,
                'birthday' => $birthday,
                'email' => $email,
                'phone' => $user_phone,
                'user_type' => $user_type,
                'orgform' => $orgform,
                'app_type' => $app_type,
                'city_id' => $city_id,
                'lat' => $lat,
                'lon' => $lon,
                'is_service' => $is_service,
                'is_shop' => $is_shop,
            ];

            if( $orgform == '2'){

                $userOpt = $user->userOpt;
                if(isset($userOpt)){

                    $reponse['short_name'] = $userOpt->short_name;
                    $reponse['full_name'] = $userOpt->full_name;
                    $reponse['inn'] = $userOpt->inn;
                    $reponse['description'] = $userOpt->description;
                    $reponse['open_hours_from'] = $userOpt->open_hours_from;
                    $reponse['open_hours_to'] = $userOpt->open_hours_to;
                    $reponse['address'] = $userOpt->address;
                    $reponse['address_lat'] = $userOpt->address_lat;
                    $reponse['address_lon'] = $userOpt->address_lon;
                    $reponse['site'] = $userOpt->site;

                }

            }

        return response()->json([

            'response' => $reponse,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }

}
