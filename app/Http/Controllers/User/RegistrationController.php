<?php

namespace App\Http\Controllers\User;

use App\Classes\ApiError;
use App\Models\City;
use App\Models\PhoneSalt;
use App\Models\User;
use App\Models\UserOpt;
use App\Traits\GeoTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class RegistrationController extends Controller
{
    use GeoTrait;

    public function checkAuthToken(Request $request)
    {

        /*$required_params = ['device_token'];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }*/

        $auth_tok = Input::get('auth_token');
        //$device_token = Input::get('device_token');

        $ph_slt_builder = PhoneSalt::where('auth_token',$auth_tok)
            //->where('device_token',$device_token)
            ->where('is_accepted',1);

        if(!$ph_slt_builder->exists()){
            $err = new ApiError(341,NULL,"Требуется войти в приложение","Токен не верный");
            return $err->json();
        }

        $phone_salt = $ph_slt_builder->first();

        $user = $phone_salt->user;

        if(isset($user)){

            $city = $user->city;

            $state = 1;
            $user_type = $user->user_type;
            $cart_count = 0;                  //todo сделать значение из бд
            $is_service = $user->is_service;
            $is_shop = $user->is_shop;
            $city_id = $user->city_id;
            $city_name = $city->name;
            $city_lat = $city->center_lat;
            $city_lon = $city->center_lon;
            $city_utc_off = $city->utc_offset;
            $phone = $user->phoneSalt->phone;

        }
        else{
            $state = 0;
            $user_type = 0;
            $cart_count = 0;
            $is_service = 0;
            $is_shop = 0;
            $city_id = 0;
            $city_name = 0;
            $phone = 0;
            $city_lat = 0;
            $city_lon = 0;
            $city_utc_off = 0;
        }

        return response()->json([

            'response' => [

                'state' => $state,
            'user_type' => $user_type,
            'cart_count' => $cart_count,
            'is_service' => $is_service,
            'is_shop' => $is_shop,
            'city' => [
                'id' => $city_id,
                'name' => $city_name,
                'center_lat' => $city_lat,
                'center_lon' => $city_lon,
                'utc_offset' => $city_utc_off,
            ],
            'phone' => $phone,

                ]

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function regUser(Request $request)
    {

        $phone_id = PhoneSalt::where('auth_token',$request->input('auth_token'))
            ->where('is_accepted',1)
            ->get(['id'])
            ->first()
            ->id;

        if(User::where('phone_id',$phone_id)->exists()){

            $err = new ApiError(341,NULL,
                'Пользователь с таким телефоном уже существует',
                "Пользователь с таким телефоном уже существует");
            return $err->json();

        }

        $required_params = [
            'name', 'email','password',
            'user_type','orgform','city_id'
        ];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }

        unset($required_params,$not_valid_param);

        $org_form = $request->input('orgform');

        if($org_form === '2'){

            $required_params = [
                'short_name','full_name',
                'inn','description','open_hours_from',
                'open_hours_to','address','site'
            ];

            $not_valid_param = $this->checkRequiredParams($required_params);

            if(isset($not_valid_param)){
                $err = new ApiError(345,
                    NULL,
                    'Заполните все поля',
                    'Не передан обязательный параметр для orgform = 2 ,'.$not_valid_param);
                return $err->json();
            }

            unset($required_params,$not_valid_param);

        }

        $user = new User;

        //Обязательные для всех параметры
        $user->name = $request->input('name');


        if($org_form !== '2'){

            if($request->has('birthday'))
                $user->birthday = $request->input('birthday');

            else{
                $err = new ApiError(345,
                    'birthday',
                    'Заполните все поля',
                    'Не передан обязательный параметр');
                return $err->json();
            }
        }



        $user->email = $request->input('email');
        $user->password = $request->input('password');
        $user->user_type = $request->input('user_type');
        $user->orgform = $org_form;

        $city_id = $request->input('city_id');
        if(City::where('id',$city_id)->exists())
        $user->city_id = $city_id;

        else{
            $err = new ApiError(349,
                NULL,
                'Нет такого города',
                'Нет такого города city_id - '.$city_id);
            return $err->json();
        }


        $user->phone_id = $phone_id;

        //Параметры не обязательные , но для всех
        if($request->exists('surname'))
            $user->surname = $request->input('surname');
        if($request->exists('lat'))
            $user->lat = $request->input('lat');
        if($request->exists('lon'))
            $user->lon = $request->input('lon');

        //Параметры для user_type = 2
        if($request->input('user_type') === '2'){

            if($request->exists('is_service'))
                $user->is_service = $request->input('is_service');

            if($request->exists('is_shop'))
                $user->is_shop = $request->input('is_shop');

        }

        //Проверка при user_type = 2
        if($user->user_type === '2'){
            if(!isset($user->is_service) && !isset($user->is_shop)){

                $err = new ApiError(343,
                    NULL,
                    'Одно из значений - продажа товара/оказание услуги должно быть выбрано',
                    'Не передан обязательный параметр для user_type = 2');
                return $err->json();

            }
            elseif($user->is_service !== '1' && $user->is_shop !== '1'){

                $err = new ApiError(344,
                    NULL,
                    'Одно из значений - продажа товара/оказание услуги должно быть выбрано',
                    'Один из обязательных параметров для user_type = 2 должен быть = 1');
                return $err->json();

            }
        }

        $user_opt = NULL;

        if($org_form === '2'){

            $user_opt = new UserOpt;
            $user_opt->short_name = $request->input('short_name');
            $user_opt->full_name = $request->input('full_name');
            $user_opt->inn = $request->input('inn');
            $user_opt->description = $request->input('description');
            $user_opt->open_hours_from = $request->input('open_hours_from').":00";
            $user_opt->open_hours_to = $request->input('open_hours_to').":00";
            $user_opt->site = $request->input('site');

            //Получение координат из адреса
            $addr = ''.$request->input('address');
            $user_opt->address = $addr;

            $resp = $this->getApiAddressCoords($addr);

            if($resp instanceof ApiError)
                return $resp->json();

            $user_opt->address_lat = $resp['lat'];
            $user_opt->address_lon = $resp['lon'];

        }

        try {
            DB::transaction(function () use ($user, $user_opt) {
                $user->save();

                if (isset($user_opt)) {

                    $user_opt->user_id = $user->id;
                    $user_opt->save();
                }

            });
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

        'response' => 1

    ],200,[],JSON_UNESCAPED_UNICODE);

    }

}
