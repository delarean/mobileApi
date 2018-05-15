<?php

namespace App\Http\Controllers;


use App\Models\PhoneSalt;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Input;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function checkRequiredParams($req_params)
    {

        foreach ($req_params as $req_param){

            if (!Input::has($req_param) || !Input::filled($req_param))
                return $req_param;
        }

        return NULL;
    }

    protected function getCurrentUser($auth_token)
    {

        $user = PhoneSalt::where('auth_token',$auth_token)
            ->where('is_accepted',1)
            ->first()
            ->user;

        return $user;
    }

    public function getCurrentUserModel($auth_token)
    {
        $user = PhoneSalt::where('auth_token',$auth_token)
            ->where('is_accepted',1)
            ->first()
            ->user()
            ->first();

        return $user;
    }

    public function timeToApiResponse($time)
    {

        $time_params = explode(':',$time);

        return $time_params[0].':'.$time_params[1];


    }


}
