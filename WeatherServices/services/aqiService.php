<?php
header ( 'Content-type: text/json;charset=UTF-8' );
date_default_timezone_set ( 'PRC' ); // set timezone to China

include_once 'AqiHelper.php';
include_once 'Helper.php';
include_once 'WeatherClass.php';

if (! empty ( $_GET ["cityname"] )) {
	$cityName = $_GET ["cityname"];

	// get AQI
    //$aqi = AqiHelper::get_aqi($aqi, $cityName);
    //$aqi = AqiHelper::query_aqi($cityName);
    $aqi = AqiHelper::query_all_aqi();
    //$aqi = AqiHelper::fetch_aqi_rank();
    if($aqi != null) {
        echo json_encode($aqi);
    }
    else {
        Logger::error($cityName . " has no pm25 data.");
    }
}
else {
	Logger::error("empty cityname");
}
