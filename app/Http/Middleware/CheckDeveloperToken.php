<?php

namespace App\Http\Middleware;

use App\Models\DevTok;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Classes\ApiError;

class CheckDeveloperToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        if(!$request->hasHeader('Authorization')) {
            $err = new ApiError(301);
            return $err->json();
        }


            $inputToken = $request->header('Authorization');

            try{

            DevTok::where('value',$inputToken)
                ->firstOrFail(['value']);
                }

                catch (ModelNotFoundException $err){
                    $err = new ApiError(302);
                    return $err->json();
            }



            return $next($request);




    }
}
