<?php
include_once "YahooWeather.php";

/**
 * Migration for v0.8.0
 */
class Modou_080
{
    public static function getWeather( $cityId )
    {
        $weather = new YahooWeather ( $cityId );
        $image_path = "res/weather/yahoo/";
        
        $result = array();
        $result["cityId"] = $weather->cityId;
        $result["city"] = $weather->city;
        $result["date"] = $weather->date;
        $result["week"] = $weather->week;
        
        $result["file_time"] = $weather->file_time;
        $result["update_time"] = $weather->update_time;
        
        $result["temp"] = $weather->temp;
        $result["weather"] = $weather->forecastArray[0]->weather;
        $result["wind"] = $weather->wind;
        $result["humidity"] = $weather->humidity;
        $result["picUrl"] = $image_path . $weather->picUrl;
        
        //forecastArray
        $result["forecastArray"] = $weather->forecastArray;
        
        array_pop($result["forecastArray"]);
        array_pop($result["forecastArray"]);        
        foreach ($result["forecastArray"] as $item)
        {
        	$item->dayPicUrl = $image_path . $item->dayPicUrl;
        }
        
        //guideArray
        $result["guideArray"] = array(); // $weather->guideArray;

        array_push($result["guideArray"], Helper::guide_item("湿度:", $weather->humidity, 0xF5F0FF));
        array_push($result["guideArray"], Helper::guide_item("", $weather->weather, 0xF5F0FF)); //weather
        array_push($result["guideArray"], Helper::guide_item("", $weather->wind, 0xF5F0FF)); //wind      
        array_push($result["guideArray"], Helper::guide_item("日出 ", $weather->sunrise));
        array_push($result["guideArray"], Helper::guide_item("日落 ", $weather->sunset));
        
        if(!is_null($weather->aqiArray))
        {
            $aqi = $weather->aqiArray["aqi"];
            $aqiMsg = sprintf("%s %s", $aqi, mb_substr($weather->aqiArray["quality"], 0, 2, 'utf-8'));
            
            $aqi_bgcolor = Helper::get_aqi_color($result, $aqi);
            array_push($result["guideArray"], Helper::guide_item("AQI:", $aqiMsg, $aqi_bgcolor));
            array_push($result["guideArray"], Helper::guide_item("PM2.5: ", " " . $weather->aqiArray["pm2_5"], $aqi_bgcolor));
        }        

        array_push($result["guideArray"], Helper::guide_item("更新 ", date("H:i")));
        
        $result["message"] = ""; 
        // "                   春节愉快!"; 
        //"Hello 魔豆天气！";

        return $result;
    }    
}
