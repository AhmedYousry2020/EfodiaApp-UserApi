<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::group(["namespace"=>"API"],function(){

    Route::get("/user","AuthController@GetUser");
    Route::post("/signin","AuthController@SignIn");
    Route::post("/signup","AuthController@SignUp");
    Route::post("/signout","AuthController@SignOut");
    Route::post("/refresh","AuthController@Refresh");
    route::post("/sendPasswordResetLink","PasswordReset@SendEmail");
    route::post("/changePassword","PasswordChange@ChangePassword");
    
    Route::get("/categories","HomeController@GetCategories");
    Route::get("/category/{category_id}/subcategories","HomeController@GetSubCategories");
    Route::get("/category/{category_id}/businesses","HomeController@GetBusinessesOfCategory");
    Route::get("/business/location/{location_id}/profile","HomeController@GetBusinessProfile");
    Route::get("/business/{business_id}/location/{location_id}/time_Card","HomeController@GetBusinessTimeCardDetails");
    Route::get("/country/{country_id}/states","HomeController@GetStates");
    Route::get("/category/businesses/filters","HomeController@FiltersOnBusinesses");

    Route::post("/reserve","ReservationController@Reserve");
    Route::get("/reservation/history/details","ReservationController@GetReservationHistory");
	Route::get("/reservation/confirm/details","ReservationController@GetReservationConfirm");
	Route::get("/reservation/waiting/details","ReservationController@GetReservationWaiting");
    
	Route::get("/business/{business_id}/location/{location_id}/day/details","ReservationController@GetSelectedDayDetails");

    //Clear config cache:
    Route::get('/config-cache', function() {
        $exitCode = Artisan::call('config:cache');
        return 'Config cache cleared';
    }); 


});
