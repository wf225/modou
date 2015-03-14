<?php
include_once "ChinaWeather.php";

/**
 * Migration for v0.7.1.
 */
class Modou_071
{
    public static function getChinaWeather( $cityId )
    {
        $weather = new ChinaWeather ( $cityId );
        
        $result = array();
        $result["cityId"] = $weather->cityId;
        $result["city"] = $weather->city;
        $result["date"] = $weather->date;
        $result["week"] = $weather->week;
        $result["weatherArray"] = $weather->forecastArray;
        $result["guideArray"] = $weather->guideArray;
        
        $result["file_time"] = $weather->file_time;
        $result["update_time"] = $weather->update_time;
                
        $result["temp"] = $weather->temp;
        $result["weather"] = $weather->forecastArray[0]->weather;
        $result["wind"] = $weather->wind;
        $result["humidity"] = $weather->humidity;
        
        foreach ($result["weatherArray"] as $item)
        {
        	$item->dayPicUrl = "res/weather/chinaweather/b_" . $item->dayPicUrl;
        }
                
        $result["aqi_fgcolor"] = 0xE16F00;
        $result["aqi_bgcolor"] = 0xEDEDED; // default= gray 0xEDEDED;
        if(!is_null($weather->aqiArray))
        {
            $aqi = $weather->aqiArray["aqi"];
            $aqiMsg = sprintf("%s %s", $aqi, mb_substr($weather->aqiArray["quality"], 0, 2, 'utf-8'));
            
            $result["guideArray"][0]->title = "AQI";
            $result["guideArray"][0]->guide = $aqiMsg;
            $result["guideArray"][0]->picUrl = "res/guide/aqi.png";
            
            $result["guideArray"][1]->title = "更新";
            $result["guideArray"][1]->guide = $weather->update_time;
            $result["guideArray"][1]->picUrl = null;
            
            if($aqi <= 50) {
                $result["aqi_bgcolor"] = 0x00FF00;
            }
            else if($aqi <= 100) {
                $result["aqi_bgcolor"] = 0x00FFFF;
            }
            else if($aqi <= 150) {
                $result["aqi_bgcolor"] = 0x25C1FF;
            }
            else if($aqi <= 200) {
                $result["aqi_bgcolor"] = 0x0000FF;
            }
            else if($aqi <= 300) {
                $result["aqi_bgcolor"] = 0xFF66E0;
            }
            else if($aqi > 300) {
                $result["aqi_bgcolor"] = 0x3F85CD;
            }
        }
        else {
            $result["guideArray"][0]->title = "Update";
            $result["guideArray"][0]->guide = $weather->update_time;
            $result["guideArray"][0]->picUrl = null;            
        }
        
        $result["message"] = //""; 
        // "                   春节愉快!"; 
        " Hello 魔豆天气！";

        return $result;
    }
    
}
