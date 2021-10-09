<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
class PasswordChange extends Controller
{
    // auth middleware on routes
    // public function __construct(){

    //     return $this->middleware("auth:api");
    // }


    public function ChangePassword(Request $request){

        $validator = Validator::make($request->all(),[

            "resetToken"=>"required",
            "email"=> "required|email",
            "password"=>"required|confirmed"
        ]);
        if($validator->fails()){
            return response()->json([
                "msg"=> $validator->errors(),
    
            ],422);
        }
        $userResettoken = DB::table("password_resets")->where(["email"=>$request->email,"token"=>$request->resetToken]);
        if(!$userResettoken){

            return response()->json([
                'msg' => 'Either your email or token is wrong.'
              ],422);
           
        }

        $user = User::where("email","=",$request->email)->first();
        $user->update([
            "password"=>Hash::make($request->password),
        ]);

        $userResettoken->delete();
        return response()->json([
            'msg'=>'Password has been updated.'
          ],201);

    }

}
