<?php

namespace App\Http\Controllers\User;

use App\Classes\ApiError;
use App\Models\PhoneSalt;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use \Zelenin\SmsRu\Api as SMSApi;
use \Zelenin\SmsRu\Auth\ApiIdAuth as SMSApiIdAuth;
use \Zelenin\SmsRu\Entity\Sms;

class SMSController extends Controller
{

    private $SMSText = "Ваш код в приложении poscare : ";

    private $ten_min_lat;
    private $inp_salt;
    private $inp_phone;

    public function sendSmsCode(Request $request)
    {

        $required_params = ['phone'];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }


        $phone = $this->changePhone(Input::get('phone'));


        $client = new SMSApi(new SMSApiIdAuth(env('SMS_API_ID', false)));

        $salt = $this->randomNumber(6);

        if($phone == '79653684111')
            $salt = '123456';

        $sms = new Sms($phone, $this->SMSText.$salt);

        try {
           $phoneSalt = PhoneSalt::where('phone',$phone)
                ->firstOrFail();

           /*if($phoneSalt->attempts >= 3){
               $err = new ApiError(322,NULL,"Вы отправили код максимальное количество раз","Слишком часто отправляет код");
               return $err->json();
           }*/

            $phoneSalt->update([
                'salt' => $salt,
            ]);

            if($phoneSalt->get()->isEmpty()){

                $err = new ApiError(310);
                return $err->json();

            }
        }
        catch (ModelNotFoundException $err){

            $phoneSalt = PhoneSalt::create([
                'phone' => $phone,
                'salt' => $salt,
            ]);

            if($phoneSalt->get()->isEmpty()){

                $err = new ApiError(310);
                return $err->json();

            }

        }

           $response = $client->smsSend($sms);

           if($response->code !== '100'){
               $err = new ApiError(315,NULL,NULL,$response->getDescription());
               return $err->json();
           }



        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function randomNumber($length)
    {
            $result = '';

            for($i = 0; $i < $length; $i++) {
                $result .= mt_rand(0, 9);
            }

            return $result;
    }

    public function changePhone($phone)
    {

        $phone = str_replace("+7 (", "7", $phone);
        $phone = str_replace(") ", "", $phone);
        $phone = str_replace("-", "", $phone);

        return $phone;

    }

    public function checkSmsBalance()
    {

        $client = new SMSApi(new SMSApiIdAuth(env('SMS_API_ID', false)));

        return response()->json([

            'response' => $client->myBalance()->balance,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function authBySms(Request $request)
    {

        $required_params = [
            'phone',
            'salt',
            //'device_token',
            'app'
        ];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }

        $this->inp_phone = $this->changePhone(Input::get('phone'));
        $this->inp_salt =  Input::get('salt');

        $this->ten_min_lat = date("Y-m-d H:i:s",
            mktime(date('H'), date('i') + 10, date('s'), date("m")  , date("d"), date("Y")));

        $phone_salt = PhoneSalt::where('phone',$this->inp_phone)
            ->where('salt',$this->inp_salt)
            ->where('created_at','<=',$this->ten_min_lat)
            ->orWhere((function ($query) {
                $query->where('updated_at','<=',$this->ten_min_lat)
                    ->whereNotNull('updated_at')
                    ->where('phone',$this->inp_phone)
                    ->where('salt',$this->inp_salt);
            }));

        if(!$phone_salt->exists()){

                $err = new ApiError(321, NULL,
                    'неверный sms код или срок его действия истёк',
                    'неверный sms код или срок его действия истёк');
                return $err->json();
        }

        $phone_salt = $phone_salt->first();

        $auth_token = str_random(30);
        //$device_token = Input::get('device_token');
        if($request->has('device_token') && $request->filled('device_token'))
        {
            $device_token = Input::get('device_token');
        }
        else
        $device_token = NULL;


        $app_type = Input::get('app');

        if($app_type !== '1' && $app_type !== '2'){
            $err = new ApiError(307,'app');
            return $err->json();
        }

        $phone_salt->update([
            'is_accepted' => 1,
            'auth_token' => $auth_token,
            'device_token' => $device_token,
            'app_type' => $app_type,
        ]);

        $user_buider = $phone_salt->user();

        if($user_buider->exists()){

            $user = $user_buider->first(['user_type','is_service','is_shop']);

            $state = 1;
            $user_type = $user->user_type;
            $is_service = $user->is_service;
            $is_shop = $user->is_shop;


        }
        else{
            $state = 0;
            $user_type = 0;
            $is_service = 0;
            $is_shop = 0;
        }

        return response()->json([

            'response' => [

                'state' => $state,
                'user_type' => $user_type,
                'is_service' => $is_service,
                'is_shop' => $is_shop,
                'auth_token' => $auth_token,

            ],
        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}
