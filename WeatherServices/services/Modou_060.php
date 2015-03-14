<?php
include_once "ChinaWeather.php";

/**
 * Migration for v0.6.0.
 */
class Modou_060
{
    public static function getChinaWeather( $cityId )
    {
        $weather = new ChinaWeather ( $cityId );
        
        $result = array();
        $result["error"] = 0;
        $result["cityId"] = $weather->cityId;
        $result["city"] = $weather->city;
        $result["date"] = $weather->date;
        $result["week"] = $weather->week;
        $result["fileTime"] = $weather->file_time;
        $result["guideArray"] = $weather->guideArray;
        $result["weatherArray"] = $weather->forecastArray;
        $result["updateTime"] = $weather->update_time;
        
        foreach ($result["weatherArray"] as $item)
        {
        	$item->dayPicUrl = "res/weather/chinaweather/b_" . $item->dayPicUrl;
        }
        
        return $result;
    }
    
    public static function getChinaWeatherNow( $cityId )
    {
        $weather = new ChinaWeather ( $cityId );
        
        $result = array();
        $result["error"] = 0;
        $result["cityId"] = $weather->cityId;
        $result["city"] = $weather->city;
        $result["temp"] = $weather->temp;
        $result["weather"] = $weather->weather;
        $result["wind"] = $weather->wind;
        $result["humidity"] = $weather->humidity;
                
        $result["updateTime"] = $weather->update_time;
        $result["fileTime"] = $weather->file_time;
        
        $result["pm25"] = sprintf("%s更新", $weather->update_time);
        $result["pm25_fgcolor"] = 0xE16F00;
        $result["pm25_bgcolor"] = 0xEDEDED; // default= gray 0xEDEDED;
        
        if(!is_null($weather->aqiArray))
        {
            $aqi = $weather->aqiArray["aqi"];
            $result["pm25"] = sprintf("AQI:%s %s", $aqi, mb_substr($weather->aqiArray["quality"], 0, 2, 'utf-8'));
            
            if($aqi <= 50) {
                $result["pm25_bgcolor"] = 0x00FF00;
            }
            else if($aqi <= 100) {
                $result["pm25_bgcolor"] = 0x00FFFF;
            }
            else if($aqi <= 150) {
                $result["pm25_bgcolor"] = 0x25C1FF;
            }
            else if($aqi <= 200) {
                $result["pm25_bgcolor"] = 0x0000FF;
            }
            else if($aqi <= 300) {
                $result["pm25_bgcolor"] = 0xFF66E0;
            }
            else if($aqi > 300) {
                $result["pm25_bgcolor"] = 0x3F85CD;
            }
        }
        
        return $result;
    }
}
