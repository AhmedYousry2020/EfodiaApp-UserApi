<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
class HomeController extends Controller
{
    const ACTIVE_STATUS = 1;
    const DEFAULT_STATUS = 'Cairo'; 
    const PAGINATION_NUM = 20;
    const confirm_STATUS=1;
    const waiting_STATUS=2; 
    const HOUR_STATES =3;
    // auth middleware on routes
    public function __construct(){

        return $this->middleware("JWTauth");
    }

    //get categories of owners
    public function GetCategories(Request $request){

        $categories = DB::table("categories")
        ->where("status","=",self::ACTIVE_STATUS)
        ->when($request->searchBy,function($query) use ($request){
            $query->where("name_en","like",'%'.$request->searchBy.'%');
        })
        ->select("id","name_en","name_ar","name_fr","image")
        ->paginate(SELF::PAGINATION_NUM);

        if($categories){
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>$categories

            ],201);
        }else{
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>"Empty rows"
            ],201);
        }
    }

    //get sub_categories of specific category
    public function GetSubCategories($category_id){

        $category = DB::table('categories')
        ->where("id","=",$category_id)
        ->first();
        if($category){
            $subcategories = DB::table("categories_sub")
            ->where("status","=",self::ACTIVE_STATUS)
            ->where("category_id","=",$category_id)
            ->select("id","category_id","name_en","name_ar","name_fr","image")
            ->paginate(SELF::PAGINATION_NUM);
            
            if($subcategories){
                return response()->json([
    
                    "msg"=>"Successfully request",
                    "data"=>$subcategories
    
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

    //get business of specific category
    public function GetBusinessesOfCategory($category_id,Request $request){
        
        $businesses = DB::table("business")
            ->join("owners","owners.id","=","business.owner_id")
            ->join("business_locations","business.id","=","business_locations.business_id")
            ->join("categories_sub","business.sub_category_id","=","categories_sub.id")
            ->leftjoin("ratings","ratings.business_id","=","business.id")
            ->leftjoin("business_emails","business_emails.business_id","=","business.id")
            ->leftjoin("business_phones","business_phones.business_id","=","business.id")
            ->where("business.category_id","=",$category_id)
            ->where("business_locations.state_name","=",SELF::DEFAULT_STATUS)
            ->select("business.id as business_id","business.name_ar as business_name_ar","business.name_en as business_name_en","business_emails.email as business_email","business_phones.phone_number as business_phone","business.image","business_locations.id as business_location_id","location_name as business_location_name","country_name as business_country_name","state_name as business_state_name","city_name as business_city_name","categories_sub.name_en as speciality_en","categories_sub.name_ar as speciality_ar","categories_sub.name_fr as speciality_fr","views","ratings.rating")
            ->paginate(SELF::PAGINATION_NUM);
        
        if($businesses){
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>$businesses

            ],201);
        }else{
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>"Empty rows"

            ],201);
        }
    }

    // filters on businessess with searchBy and categeory and state location
    public function FiltersOnBusinesses(Request $request){
       
        if(!$request->state){
            $request->state = SELF::DEFAULT_STATUS;
        }
        $businesses = DB::table("business")
        ->join("owners","owners.id","=","business.owner_id")
        ->join("business_locations","business.id","=","business_locations.business_id")
        ->join("categories","business.category_id","=","categories.id")
        ->join("categories_sub","business.sub_category_id","=","categories_sub.id")
        ->leftjoin("ratings","ratings.business_id","=","business.id")
        ->leftjoin("business_emails","business_emails.business_id","=","business.id")
        ->leftjoin("business_phones","business_phones.business_id","=","business.id")
        ->where("business_locations.state_name","like",$request->state)
        ->when($request->category,function($query) use ($request){
         $category = DB::table('categories')->where("name_en","like",$request->category)->first();
           return $query->where("business.category_id","=",$category->id);
        })
		//->when($request->searchBy,function($query) use ($request) {
           
        //    $query->where("business.name_en","like",'%'.$request->searchBy.'%')
        //    ->orWhere("business.name_en","like",'%'.$request->searchBy.'%');  
        //})
        ->select("business.id as business_id","business.name_ar as business_name_ar","business.name_en as business_name_en","business_emails.email as business_email","business_phones.phone_number as business_phone","business.image","business_locations.id as business_location_id","location_name as business_location_name","country_name as business_country_name","state_name as business_state_name","city_name as business_city_name","categories_sub.name_en as speciality_en","categories_sub.name_ar as speciality_ar","categories_sub.name_fr as speciality_fr","views","ratings.rating")
        
		->paginate(SELF::PAGINATION_NUM);
		
			
		$businesses_searchBy = array();
	
		if($request->searchBy){
		   foreach($businesses as $busienss){
			   if(preg_match('/'.$request->searchBy.'/i',$busienss->business_name_en)){
				      array_push($businesses_searchBy,$busienss);
			   }
		   }
			   if($businesses_searchBy){
				return response()->json([

					"msg"=>"Successfully request",
					"data"=>["data"=>$businesses_searchBy]

				],201);
			}else{
				return response()->json([

					"msg"=>"Successfully request",
					"data"=>"Empty rows"

				],201);
			}
		   
	   }
	   
	   if($businesses){
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>$businesses

            ],201);
        }else{
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>"Empty rows"

            ],201);
        }
    }

    // get specific busienss profile
    public function GetBusinessProfile($location_id){
        
        $location =  DB::table('business_locations')->where("id","=",$location_id)->first();
        if($location){

            $business = DB::table("business")
            ->join("business_locations","business.id","=","business_locations.business_id")
            ->join("categories_sub","business.sub_category_id","=","categories_sub.id")
            ->leftjoin("ratings","ratings.business_id","=","business.id")
            ->leftjoin("business_emails","business_emails.business_id","=","business.id")
            ->leftjoin("business_phones","business_phones.business_id","=","business.id")
            ->where("business_locations.id","=",$location_id)
            ->select("business.id as business_id","business.name_ar as business_name_ar","business.name_en as business_name_en","business_emails.email as business_email","business_phones.phone_number as business_phone","business.image","business_locations.id as business_location_id","location_name as business_location_name","country_name as business_country_name","state_name as business_state_name","city_name as business_city_name","categories_sub.name_en as speciality_en","categories_sub.name_ar as speciality_ar","categories_sub.name_fr as speciality_fr","views","ratings.rating")
            ->first();

                if($business){
        
                DB::table('business')
                    ->where('id', $business->business_id)
                    ->increment('views', 1);
                }

                return response()->json([
                    "msg"=>"Successfully request",
                    "business_details"=>$business,
        
                ],201);
               
            }else{
            return response()->json([

                "msg"=>"Successfully request",
                "data"=>"Empty rows"

            ],201);
        }

       
    }

    //get business working time details
    public function GetBusinessTimeCardDetails($business_id,$location_id){
    
        $business =  DB::table('business')->where("id","=",$business_id)->first();
        $location =  DB::table('business_locations')->where("id","=",$location_id)->first();
        

    if($location && $business){

            $Daydetails = DB::table('reservations')
            ->where("business_id","=",$business_id)
            ->where("location_id","=",$location_id)
            ->where(function($q){
               
                 $q->where("status","=",self::confirm_STATUS)
                 ->orWhere("status","=",self::waiting_STATUS);
            })
            ->select(DB::raw('count(*) as clientsCount'))
            ->selectRaw('date(created_at) as day')
            ->groupBy("day")
            ->get();

            $business_setting = DB::table('business_settings')
            ->where("business_id","=",$business_id)
            ->select("capacity","work_capacity")
            ->first();
            
            $time_card = DB::table('business_time_cards')
            ->where("business_id","=",$business->id)
            ->where("location_id","=",$location->id)
            ->first(); 

            if(empty($time_card)){
                
                return response()->json([

                "msg"=>"Successfully request",
                "data"=>"Empty rows"

                ],201); 
            }

            $BusinessTimeCardDetails = DB::table('business_work_hours')
            ->where("time_card_id","=",$time_card->id)
            ->select("id","time_card_id","start_hour","end_hour","su","mo","tu","we","th","fr","sa")
            ->first();     
            
            if(empty($BusinessTimeCardDetails)){
                
                return response()->json([

                "msg"=>"Successfully request",
                "data"=>"Empty rows"

                ],201); 
            }
            $capacity =  $this->GetCapacityAverage($business_setting->capacity,$business_setting->work_capacity,$BusinessTimeCardDetails->start_hour,$BusinessTimeCardDetails->end_hour);
            
            //capacity that can be used to detect state of owner now
    
            $capacityAllowed = (int)$capacity / self::HOUR_STATES;
            $crowdedState = $capacity;
            $semiCrowdedState = (int)$capacity - (int)($capacity/2);
            $NotCrowdedState = $capacityAllowed ;
           
            foreach($Daydetails as $Daydetail){
                
                if($Daydetail->clientsCount <= $NotCrowdedState ){
                   
                    $Daydetail->status = "NotCrowded State";
                }else if($Daydetail->clientsCount >= $semiCrowdedState && $Daydetail->clientsCount < $crowdedState ){
                    $Daydetail->status = "semi crowded";
                }else{
                    $Daydetail->status = "crowded";  
                }
    
            }
            return response()->json([

                 "msg"=>"Successfully request",
                 "timeCardDetails"=>$BusinessTimeCardDetails,
                 "daysState"=>$Daydetails
                            
                ],201);
            
    }else{
        return response()->json([

            "msg"=>"Successfully request",
            "data"=>"Empty rows"
                       
           ],201);
    } 
    }

    //function to get all states in specific country
    public function GetStates($country_id){
      
        $country = DB::table('locations_countries')->where("id","=",$country_id)->first();
        $categories = DB::table("categories")
             ->where("status","=",self::ACTIVE_STATUS)
             ->select("name_en","name_ar","name_fr")
             ->get();
       if($country){
            
            $states = DB::table('locations_states')
            ->where("country_id","like",$country->id)
            ->select("name_en","name_ar","name_fr")
            ->get();
                     
            return response()->json([

                "msg"=>"Successfully request",
                "states"=>$states,
                "categories"=>$categories    
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

    
}
