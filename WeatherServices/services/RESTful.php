<?php
header ( 'Content-type: text/json;charset=UTF-8' );
date_default_timezone_set ( 'PRC' ); // set timezone to China

include_once 'libs/flight/Flight.php';
include_once "ChinaWeather.php";
include_once "ChinaWeatherNow.php";
include_once 'AqiHelper.php';
include_once "Modou_080.php";

Flight::route('/', function(){
    Logger::error("Hello modouweather: /@serviceName/@parm");
});

// modouweather - v0.80
Flight::route('/weather/v080/@cityName/@mac_address', function($cityName, $mac_address){
    $userLog = new User_access_log();
    $userLog->mac_address = str_replace(':', '-', $mac_address);
    $userLog->cityId = $cityName;
    Helper::save_user_access_log($userLog);

    $result = Modou_080::getWeather($cityName);
    echo json_encode($result);
});

Flight::route('/weather/@cityName/@mac_address', function($cityName, $mac_address){
    $userLog = new User_access_log();
    $userLog->mac_address = str_replace(':', '-', $mac_address);
    $userLog->cityId = $cityName;
    Helper::save_user_access_log($userLog);

    $result = Modou_080::getWeather($cityName);
    echo json_encode($result);
});


//AQI
Flight::route('/aqi/@cityName', function($cityName){
    //$aqi = AqiHelper::query_aqi($cityName);
    $aqi = AqiHelper::read_aqi($cityName);
	if($aqi != null) {
		echo json_encode($aqi);
	}
	else {
		Logger::error($cityName . " has no aqi data.");
	}
});

Flight::route('/aqi', function(){
    $aqi = AqiHelper::query_all_aqi();
    //$aqi = AqiHelper::fetch_aqi_rank();
	if($aqi != null) {
		echo json_encode($aqi->data);
	}
	else {
		Logger::error($cityName . " has no aqi data.");
	}
});

/**
 * Fetch data: fetch_aqi_rank, fetch_condition, fetch_forecast
 */
Flight::route('/fetch_data', function(){
    $cityId = 101020100;
    
    $aqi_rank = AqiHelper::fetch_aqi_rank();        
	if(!is_null($aqi_rank)) {
		Logger::log("fetch aqi_rank successful.");
	}
    else {
        Logger::log("fetch aqi_rank failed.");
    }
    
    /*
	$condition = ChinaWeather::fetch_condition($cityId);
	if(!is_null($condition)) {
        Logger::log("fetch condition successful, " . $cityId);
	}
    else {
        Logger::log("fetch condition failed, " . $cityId);
    }
    
    $result = ChinaWeather::fetch_forecast($cityId);
	if($result != null) {
        Logger::log("fetch_forecast successful, " . $cityId);
	}
	else {
		Logger::log("fetch_forecast failed, " . $cityId);
	}
    */
});

Flight::start();

?>
