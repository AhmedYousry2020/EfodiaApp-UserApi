<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;

use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
class JWTAuthentication
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
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
        } catch (\Exception $e) {

            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
               $newToken = JWTAuth::parseToken()->refresh();
                return response()->json(['status'=>'false','message' => 'token-expired','token'=>$newToken],401);
                
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['status'=>'false','message' => 'invalid-token'],401);
            }else if($e instanceof \Tymon\JWTAuth\Exceptions\TokenBlacklistedException){
                return response()->json(['status'=>'false','message' => 'token-blacklisted'],401);
            }else{
                return response()->json(['status'=>'false','message' => 'token-not-found'],401);
            }
        }
        return $next($request);
    }
}
