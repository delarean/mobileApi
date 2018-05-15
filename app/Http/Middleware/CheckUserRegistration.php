<?php

namespace App\Http\Middleware;

use App\Classes\ApiError;
use App\Models\PhoneSalt;
use Closure;

class CheckUserRegistration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $input_auth_tkn = $request->input('auth_token');

        $user = PhoneSalt::where('auth_token',$input_auth_tkn)
            ->where('is_accepted',1)
            ->first()
            ->user();

        if(!$user->exists()){
            $err = new ApiError(300);
            return $err->json();
        }

        return $next($request);
    }
}
