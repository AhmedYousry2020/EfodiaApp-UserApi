<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Mail\SendMailreset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class PasswordReset extends Controller
{
    //  // auth middleware on routes
    //  public function __construct(){

    //     return $this->middleware("auth:api");
    // }

    //send reset password link 
    public function SendEmail(Request $request){

        $validator = Validator::make($request->all(),[

            "email"=> "required|email"
        ]);
        if($validator->fails()){
            return response()->json([
                "msg"=> $validator->errors(),
    
            ],422);
        }

        $user = User::where('email','=',$request->email)->first();
        if(!$user){
            return response()->json([
                    
                "msg"=> 'Email does\'t found on our database',
    
            ],401);

        }

        $usertoken = DB::table("password_resets")->where("email",$request->email)->first();
        if($usertoken){

            $token = $usertoken->token;
        }else{
            // $token = Str::random(40);
            $token = rand(100000,999999);
            DB::table('password_resets')->insert([
                'email' => $user->email,
                'token' => $token,
                
            ]);

        }


        // Mail::to($user->email)->send(new SendMailreset($token, $user->email)); 
        Mail::send(
        'emails.forgot',
        ['user'=>$user,'code'=>$token],
        function($message) use ($user){
            $message->to($user->email);
            $message->subject("$user->name,reset your password.");
        }
        );

        return response()->json([
            'msg' => 'Reset Email is send successfully, please check your inbox.'
        ],200);


    }

}
