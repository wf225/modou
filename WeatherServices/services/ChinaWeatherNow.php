<?php
include_once 'Helper.php';
include_once 'WeatherClass.php';
include_once 'AqiHelper.php';

class ChinaWeatherNow extends Condition {
	
	/**
     * 当前天气,空气质量
	 */
	static function fetch_condition($cityId) {
		$domain = AppKeys::SEA_DOMAIN_WEATHER;
		$filename = sprintf(AppKeys::WEATHER_NOW_FILE, $cityId);
		$interval = AppKeys::INTERVAL_WEATHER_NOW;
		$target_url = sprintf(AppKeys::WEATHER_NOW_URL, $cityId);
		
		return Helper::fetch_data_to_sae($domain, $filename, $interval, $target_url);
	}
	
	function __construct($cityId) {
		
		$obj = ChinaWeatherNow::fetch_condition($cityId);
		$this->cityId = $cityId;
		
		$weatherInfo = $obj->data->weatherinfo;
		if($weatherInfo != null)
		{
			$this->city = $weatherInfo->city;
			$this->temp = $weatherInfo->temp;
			$this->wind = sprintf("%s%s", $weatherInfo->WD, $weatherInfo->WS);
			$this->humidity = $weatherInfo->SD;
			$this->update_time = $weatherInfo->time;
            $this->pm25 = sprintf("%s更新", $weatherInfo->time);
            $this->pm25_fgcolor = 0xE16F00;
            $this->pm25_bgcolor = 0xEDEDED; // default= gray 0xEDEDED;
			$this->file_time = $obj->file_time;
			
			// get AQI
            $municipalityName = $this->isMunicipality($cityId);
            if($municipalityName != false)
            {
				$this->aqiArray = AqiHelper::query_aqi($municipalityName);
            }
			else
			{
				$this->aqiArray = AqiHelper::query_aqi($weatherInfo->city);
			}
            
            if(!is_null($this->aqiArray))
            {
                $aqi = $this->aqiArray;
                $this->pm25 = sprintf("AQI:%s %s", $aqi->aqi, mb_substr($aqi->quality, 0, 2, 'utf-8'));
                
                if($aqi->aqi <= 50) {
                    $this->pm25_bgcolor = 0x00FF00;
                }
                else if($aqi->aqi <= 100) {
                    $this->pm25_bgcolor = 0x00FFFF;
                }
                else if($aqi->aqi <= 150) {
                    $this->pm25_bgcolor = 0x25C1FF;
                }
                else if($aqi->aqi <= 200) {
                    $this->pm25_bgcolor = 0x0000FF;
                }
                else if($aqi->aqi <= 300) {
                    $this->pm25_bgcolor = 0xFF66E0;
                }
                else if($aqi->aqi > 300) {
                    $this->pm25_bgcolor = 0x3F85CD;
                }
            }
		}
		else{
			Logger::error("current weather condition return null.");	
		}
	}
    
    function isMunicipality($cityid)
	{
		$array = array(
			"10101" => "北京", //   "101010100"
			"10102" => "上海", //   "101020100"
			"10103" => "天津", //   "101030100"
			"10104" => "重庆", //   "101040100"
			);
		
		$key = substr($cityid, 0, 5);
		if(array_key_exists($key, $array)) {
			return $array[$key];
		}
		
		return false;
	}
	
	function toJSON()
	{
		return json_encode($this);
	}
}

?>