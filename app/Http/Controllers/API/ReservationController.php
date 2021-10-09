<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DateTime;


class ReservationController extends Controller
{
    const WAITING_QUEUE_AUTOMATIC_confirm = 1;
    const WAITING_QUEUE_MANUAL_waiting = 2;
    const HISTORY_STATUS=3;
    const confirm_STATUS=1;
    const waiting_STATUS=2;
    
    const HOUR_STATES =3;
	
    // auth middleware on routes
    public function __construct(){

        return $this->middleware("JWTauth");
    }

    // reserve an appointment with specific owner
    public function Reserve(Request $request){

        $validator = Validator::make($request->all(),[

            "business_id"=>"required",
            "location_id"=>"required",
            "created_at"=>"required|date",    
    
        ]);
       if($validator->fails()){

        return response()->json([
            "msg"=> $validator->errors(),

        ],422);
       }
        $user_id = Auth::guard("api")->user()->id;
        $business_setting = DB::table('business_settings')
        ->where("business_id","=",$request->business_id) 
        ->select("waiting_queue")->first();
        
        if($business_setting->waiting_queue == self::WAITING_QUEUE_AUTOMATIC_confirm){

            DB::table('reservations')->insert(
                [
                 'business_id' => $request->business_id,
                 'location_id'=>$request->location_id,
                 'user_id'=>$user_id,
                 'created_at'=>$request->created_at,
                 'status'=>self::confirm_STATUS, 
                ]
            );
            return response()->json([
    
                "msg"=>"Successfully confirm request",
            ],201);
    
        }else{

            DB::table('reservations')->insert(
                [
                 'business_id' => $request->business_id,
                 'location_id'=>$request->location_id,
                 'user_id'=>$user_id,
                 'created_at'=>$request->created_at,
                 'status'=>self::waiting_STATUS, 
                ]
            );
            return response()->json([
    
                "msg"=>"Successfully request but waiting in list queue",
            ],201);
        }
               
    }


    //function to get of selected day details(crowded,semi crowded,empty)
    public function GetSelectedDayDetails($business_id,$location_id,Request $request){

        $business =  DB::table('business')->where("id","=",$business_id)->first();
        $location =  DB::table('business_locations')->where("id","=",$location_id)->first();
        if($business && $location){
			
			$business_setting = DB::table('business_settings')
            ->where("business_id","=",$business_id)
            ->select("capacity","work_capacity")
            ->first();
            
            $time_card = DB::table('business_time_cards')
            ->where("business_id","=",$business_id)
            ->where("location_id","=",$location_id)
            ->first();
    
            $business_work_Card = DB::table('business_work_hours')
            ->where("time_card_id","=",$time_card->id)
            ->select("start_hour","end_hour")
            ->first();
			
			$hours = $this->GetHoursOfWorkDay($business_work_Card->start_hour,$business_work_Card->end_hour);
			
			$capacity =  $this->GetCapacityAverage($business_setting->capacity,$business_setting->work_capacity,$business_work_Card->start_hour,$business_work_Card->end_hour);
            
            //capacity that can be used to detect state of owner now
    
            $capacityAllowed = (int)$capacity / self::HOUR_STATES;
            $crowdedState = $capacity;
            $semiCrowdedState = (int)$capacity - (int)($capacity/2);
            $NotCrowdedState = $capacityAllowed;
			
			$arrDaydetails = array();
            for($i = 0; $i< sizeof($hours); $i++){
				$terms = explode("-",$hours[$i]);
				
				$Daydetails = DB::table('reservations')
				->where("business_id","=",$business_id)
				->where("location_id","=",$location_id)
			   
				->where(function($q){
				   
					 $q->where("status","=",self::confirm_STATUS)
					 ->orWhere("status","=",self::waiting_STATUS);
				})
				->whereBetween("created_at",[$request->daySelectd.' '.$terms[0],$request->daySelectd.' '.$terms[1]])
			
				->get()->count();
				
				$HourObject['clientsCount'] = $Daydetails;
				$HourObject['hour'] = $hours[$i]; 
				
				    if($Daydetails <= $NotCrowdedState ){
                   
						$HourObject['status'] = "NotCrowded State";
					}else if($Daydetails >= $semiCrowdedState && $Daydetails < $crowdedState ){
						$HourObject['status'] = "semi crowded";
					}else{
						$HourObject['status'] = "crowded";  
					}
					
                array_push($arrDaydetails,$HourObject);				
			}
            
            
            if($arrDaydetails){
                return response()->json([
    
                    "msg"=>"Successfully request",
                    "data"=>$arrDaydetails
    
                ],201);
            }else{
                return response()->json([
    
                    "msg"=>"Successfully request",
                    "data"=>"Empty rows"
    
                ],201);
            }
    
        }else{
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>"Empty rows"

            ],201); 
        }
       
    } 

    //reservation of history status
     public function GetReservationHistory(){
        
        $user = Auth::guard("api")->user();
		
        //$reservation = DB::select('select * from `reservations` where user_id = ? and status = ?',[$user->id,self::HISTORY_STATUS]);
        $reservation = DB::table("reservations")
		->join("business","business.id","=","reservations.business_id")
        ->join("business_locations","reservations.location_id","=","business_locations.id")
		->join("categories","business.category_id","=","categories.id")
        ->join("categories_sub","business.sub_category_id","=","categories_sub.id")
        ->leftjoin("ratings","ratings.business_id","=","business.id")
        ->leftjoin("business_emails","business_emails.business_id","=","business.id")
        ->leftjoin("business_phones","business_phones.business_id","=","business.id")
		->where("reservations.user_id","=",$user->id)
		->where("reservations.status","=",self::HISTORY_STATUS)
		->select("business.id as business_id","business.name_ar as business_name_ar","business.name_en as business_name_en","business.image","reservations.status","business_emails.email as business_email","business_phones.phone_number as business_phone","business_locations.id as business_location_id","location_name as business_location_name","country_name as business_country_name","state_name as business_state_name","city_name as business_city_name","categories_sub.name_en as speciality_en","categories_sub.name_ar as speciality_ar","categories_sub.name_fr as speciality_fr","views","ratings.rating","reservations.created_at")
		->get();

		if($reservation){
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>$reservation

            ],201);
        }else{
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>"Empty rows"

            ],201);
        }
    }
	//reservation of confirm status
     public function GetReservationConfirm(){
        
        $user = Auth::guard("api")->user();
       $reservation = DB::table("reservations")
		->join("business","business.id","=","reservations.business_id")
        ->join("business_locations","reservations.location_id","=","business_locations.id")
		->join("categories","business.category_id","=","categories.id")
        ->join("categories_sub","business.sub_category_id","=","categories_sub.id")
        ->leftjoin("ratings","ratings.business_id","=","business.id")
        ->leftjoin("business_emails","business_emails.business_id","=","business.id")
        ->leftjoin("business_phones","business_phones.business_id","=","business.id")
		->where("reservations.user_id","=",$user->id)
		->where("reservations.status","=",self::confirm_STATUS)
		->select("business.id as business_id","business.name_ar as business_name_ar","business.name_en as business_name_en","business.image","reservations.status","business_emails.email as business_email","business_phones.phone_number as business_phone","business_locations.id as business_location_id","location_name as business_location_name","country_name as business_country_name","state_name as business_state_name","city_name as business_city_name","categories_sub.name_en as speciality_en","categories_sub.name_ar as speciality_ar","categories_sub.name_fr as speciality_fr","views","ratings.rating","reservations.created_at")
		->get();
        if($reservation){
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>$reservation

            ],201);
        }else{
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>"Empty rows"

            ],201);
        }

    }
	//reservation of waiting status
     public function GetReservationWaiting(){
        
        $user = Auth::guard("api")->user();
        $reservation = DB::table("reservations")
		->join("business","business.id","=","reservations.business_id")
        ->join("business_locations","reservations.location_id","=","business_locations.id")
		->join("categories","business.category_id","=","categories.id")
        ->join("categories_sub","business.sub_category_id","=","categories_sub.id")
        ->leftjoin("ratings","ratings.business_id","=","business.id")
        ->leftjoin("business_emails","business_emails.business_id","=","business.id")
        ->leftjoin("business_phones","business_phones.business_id","=","business.id")
		->where("reservations.user_id","=",$user->id)
		->where("reservations.status","=",self::waiting_STATUS)
		->select("business.id as business_id","business.name_ar as business_name_ar","business.name_en as business_name_en","business.image","business_emails.email as business_email","business_phones.phone_number as business_phone","reservations.status","business_locations.id as business_location_id","location_name as business_location_name","country_name as business_country_name","state_name as business_state_name","city_name as business_city_name","categories_sub.name_en as speciality_en","categories_sub.name_ar as speciality_ar","categories_sub.name_fr as speciality_fr","views","ratings.rating","reservations.created_at")
		->get();
        if($reservation){
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>$reservation

            ],201);
        }else{
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>"Empty rows"

            ],201);
        }
    
    }

    //function to get avg capacity in hour  
    private function GetCapacityAverage($capacity,$workCapacity,$start_work,$end_work){

        $avgClients = $capacity * ($workCapacity/100) ;
        $time1 = new DateTime($start_work);
        $time2 = new DateTime($end_work);
        $duration = $time1->diff($time2);
        $duration = $duration->format('%h');

        $Avgcapacity = $avgClients / $duration;
        
        return (int)$Avgcapacity;
    }
	
	private function GetHoursOfWorkDay($start_hour,$end_hour){
		
		$startTime = strtotime($start_hour);
		$endTime = strtotime($end_hour);
        $thisDate = array();
		// Loop between timestamps, 24 hours at a time
		for ( $i = $startTime; $i < $endTime; $i = $i + 3600 ) {
			  
			 array_push($thisDate,date('h', $i ).'-'.date('h', $i + 3600 ));
		}
        return $thisDate;
			
			
	}


}
