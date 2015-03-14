<?php
header ( 'Content-type: text/json;charset=UTF-8' );
header( 'Access-Control-Allow-Origin: *');
date_default_timezone_set ( 'PRC' ); // set timezone to China

// Yahoo weather
include_once 'YahooWeather.php';
include_once 'Modou_080.php';

$cityName = $_GET ["city"];
$cityId = "2151849";// "CHXX0116";
//$cityName = "ио╨ё";

$yweather = new YahooWeather ( $cityName );
echo(json_encode($yweather));

//$yweather = Modou_080::getWeather ( $cityName );
//echo(json_encode($yweather));