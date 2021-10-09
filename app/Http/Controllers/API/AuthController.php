<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use JWTAuth;

class AuthController extends Controller
{
     const USER_ACTIVE = 1;

    // auth middleware on routes
    public function __construct(){

        return $this->middleware("JWTauth",["except"=>["SignIn","SignUp","Refresh"]]);
    }

    //sign up function 
    public function SignUp(Request $request){

        $validator = Validator::make($request->all(),[

            "first_name"=>"required",
            "last_name"=>"required",
            "profile_picture"=>"required|image|mimes:jpg,png,jpeg,gif,svg",
            "email"=>['required','email',Rule::unique("users")],
            "password"=>"required|string|min:6",
            "birth_date"=>"required|date",
            "gender"=>"required",
            "phone_number1"=>"required|regex:/(01)[0-9]{9}/|max:11|unique:users_phones,phone_number",
            "phone_number2"=>"regex:/(01)[0-9]{9}/|max:11|unique:users_phones,phone_number",
            
    
        ]);
       if($validator->fails()){

        return response()->json([
            "msg"=> $validator->errors(),

        ],422);
       }
       $requestAll = $validator->validated();
       $requestAll['password'] = Hash::make($request->password);
       
       $ImageName = $this->UploadPhoto($requestAll['profile_picture'],"uploads/images"); 
       $requestAll =   array_merge($requestAll,['profile_picture'=>$ImageName]);
       $requestAll["status"] = self::USER_ACTIVE; 
       if($requestAll){
   
        $user = User::create($requestAll);
        if($user){
            $token = JWTAuth::fromUser($user);
        }
        $phones_numbers = array();
        
        if(isset($requestAll['phone_number2'])){
            array_push($phones_numbers,$requestAll['phone_number1'],$requestAll['phone_number2']);

        }else{
            array_push($phones_numbers,$requestAll['phone_number1']);
        }
        
        foreach($phones_numbers as $phone_number){
            DB::table('users_phones')->insert(
                ['user_id' => $user->id, 'phone_number' =>  $phone_number,'created_at'=> date('Y-m-d H:i:s')]
            );
        }
        
        $phones = DB::table("users_phones")
        ->where("user_id","=",$user->id)
        ->select("phone_number")
        ->get();
        
        
        return response()->json([
 
            "msg"=> "Successfully registered",
            "user"=> $user,
            "phones"=>$phones,
            "token"=>$token 
        ],201); 
    } 
    }

    // sign in function
    public function SignIn(Request $request){

        $validator = Validator::make($request->all(),[

            "email"=>"required|email",
            "password"=>"required",
                
        ]);
       if($validator->fails()){

        return response()->json([
            "msg"=> $validator->errors(),
          
        ],422);
       }
       $requestAll = $validator->validated();
       $credentials = [
           "email"=>$requestAll['email'],
           "password"=>$requestAll['password']
       ];

       if($token = Auth::guard("api")->attempt($credentials)){

           $user = Auth::guard("api")->user();
           return response()->json([
 
            "msg"=> "Successfully logged",
            "user"=>$user,
            "token"=> $token
           ],201); 
       }
       else{

        return response()->json([
 
            "msg"=> "Unauthorized",
           ],401); 

       }
    }

    //get auth user 
    public function GetUser(){

        $user = Auth::guard("api")->user();
        $phones = DB::table("users_phones") 
        ->where("user_id","=",$user->id)
        ->select("phone_number")
        ->get();
	
        if($user){
            return response()->json([
                "user"=> $user,
                "phones"=>$phones
               ],201);
    
        }else{
            return response()->json([
                "msg"=>"Not Found"
               ],401);
    
        }
        
    } 

    // sign out function
    public function SignOut(){
      
         Auth::guard("api")->logout();
         JWTAuth::invalidate(JWTAuth::parseToken());
         return response()->json([
            
            'msg' => 'Successfully logout'
        ], 200);

    }

    //refresh token
    public function Refresh()
    {

         return response()->json([
            "access_token"=>Auth::guard("api")->refresh(),
            'token_type' => 'bearer',
   
           ],201);
    }
    

    function UploadPhoto($image,$folder){

        $imageName = $image->getClientOriginalName();
        $path = $image->storeAs($folder, $imageName, 'public');
        return $imageName;
    
    }
    

}
