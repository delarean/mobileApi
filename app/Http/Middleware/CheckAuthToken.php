<?php

namespace App\Http\Middleware;

use App\Models\PhoneSalt;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Classes\ApiError;

class CheckAuthToken
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

        if(!$request->has('auth_token') || !$request->filled('auth_token')) {
            $err = new ApiError(303);
            return $err->json();
        }

        $input_auth_tkn = $request->input('auth_token');

        try {
            PhoneSalt::where('auth_token', $input_auth_tkn)
                ->where('is_accepted',1)
                ->firstOrFail(['auth_token']);
        }
        catch (ModelNotFoundException $err){
            $err = new ApiError(309);
            return $err->json();
        }

        return $next($request);
    }
}
