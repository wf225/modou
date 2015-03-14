<?php
header ( 'Content-type: text/json;charset=UTF-8' );
header( 'Access-Control-Allow-Origin: *');
date_default_timezone_set ( 'PRC' ); // set timezone to China


/*
include_once "Modou_071.php";


if (empty ( $_GET ["cityid"] )) {
    Logger::error("cityid is required.");
    return;
}
if (empty ( $_GET ["mac"] )) {
    Logger::error("mac_address is required.");
    return;
}

$cityId = $_GET ["cityid"];
$mac_address = $_GET ["mac"];

$userLog = new User_access_log();
$userLog->mac_address = str_replace(':', '-', $mac_address);
$userLog->cityId = $cityId;
Helper::save_user_access_log($userLog);

$result = Modou_071::getChinaWeather($cityId);
echo json_encode($result);
*/

// get from china weather api.
/*
$url = "http://open.weather.com.cn/data/?areaid=%s&type=%s&date=%s&appid=%s";
$areaid = "101020100";
$type = "forecast_f";
$date = date("YmdHi"); //201211281030;
$appid = "a9949084f6c2c681";
$private_key = "a8150d_SmartWeatherAPI_6ac9bb7";
$public_key = sprintf($url, $areaid, $type, $date , $appid);
$key = base64_encode(hash_hmac("sha1", $public_key, $private_key, TRUE));

$appid = substr($appid, 0, 6);
$target_url = sprintf($url, $areaid, $type, $date , $appid);
$target_url = $target_url . "&key=" . urlencode($key);

$json = Helper::get_url_content ( $target_url );
$obj = json_decode ( $json );
echo($json);
 */

// Yahoo weather
include_once 'YahooWeather.php';

$cityCode = "CHXX0116";
//$cityCode = $_GET ["city"];
$yweather = new YahooWeather ( $cityCode );
echo(json_encode($yweather));