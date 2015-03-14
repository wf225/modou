<?php
include_once 'Helper.php';
include_once 'WeatherClass.php';
include_once 'AqiHelper.php';

class ChinaWeather extends IMWeather {
	
	/**
     * 未来预报, 生活指数
     */
	static function fetch_forecast($cityId) {
		$domain = AppKeys::SEA_DOMAIN_WEATHER;
		$filename = sprintf(AppKeys::WEATHER_FILE, $cityId);
		$interval = AppKeys::INTERVAL_WEATHER;
		$target_url = sprintf(AppKeys::WEATHER_URL, $cityId);
		
		return Helper::fetch_data_to_sae($domain, $filename, $interval, $target_url);
	}
    
    /**
     * 当前天气, 空气质量
     */
	static function fetch_condition($cityId) {
		$domain = AppKeys::SEA_DOMAIN_WEATHER;
		$filename = sprintf(AppKeys::WEATHER_NOW_FILE, $cityId);
		$interval = AppKeys::INTERVAL_WEATHER_NOW;
		$target_url = sprintf(AppKeys::WEATHER_NOW_URL, $cityId);
        
		return Helper::fetch_data_to_sae($domain, $filename, $interval, $target_url);
	}
	
	function __construct($cityId) {
        $this->cityId = $cityId;

        // 当前天气,空气质量
        $obj = ChinaWeather::fetch_condition($cityId);
        if(!is_null($obj)){
            $this->init_weather_condition($obj->data->weatherinfo);
            $this->file_time = $obj->file_time;
        }
        
        // 未来预报, 生活指数
		$obj = ChinaWeather::fetch_forecast($cityId);
        if(!is_null($obj)){
            $this->init_weather_forecast($obj->data->weatherinfo);
        }
        
        // AQI
        $this->init_aqi($cityId);
	}
    
    private function init_weather_condition($weatherInfo)
    {
        if(is_null($weatherInfo))
		    return;
        
        $this->city = $weatherInfo->city;
        $this->temp = $weatherInfo->temp;
        $this->wind = sprintf("%s%s", $weatherInfo->WD, $weatherInfo->WS);
        $this->humidity = $weatherInfo->SD;
        $this->update_time = $weatherInfo->time;
    }
    
    private function init_aqi($cityId)
    {
        $municipalityName = $this->is_municipality($cityId);
        if($municipalityName != false)
        {
            //$this->aqiArray = AqiHelper::query_aqi($municipalityName);
            $this->aqiArray = AqiHelper::read_aqi($municipalityName);
        }
        else
        {
            //$this->aqiArray = AqiHelper::query_aqi($weatherInfo->city);
            $this->aqiArray = AqiHelper::read_aqi($city);
        }
    }
    
    private function init_weather_forecast($weatherInfo)
    {
        if(is_null($weatherInfo))
		    return;
        
        $this->city = $weatherInfo->city;
        $this->date = $weatherInfo->date_y;
        $this->week = $weatherInfo->week;
        
        $this->init_weather_guide($weatherInfo);
        //$imagePath = "res/weather/chinaweather/b_%s.gif";
        $imagePath = "%s.gif";
        
        // get the forecast from the json.
        $this->forecastArray = array ();
        $weatherInfo = (array)$weatherInfo; // parse the json obj to array.
        
        for ($i=1; $i<=6; $i++)
        {
            $forecast = new Forecast ();
            $forecast->weather = $weatherInfo["weather" . $i];
            $forecast->week = Helper::getChineseDay( (date("w") + $i - 1) % 7 );
            
            // get low and hight temperature.
            $tempArr = $this->parse_temp( $weatherInfo["temp" . $i] );
            $forecast->low = $tempArr[ "low" ];
            $forecast->high = $tempArr[ "high" ];
            
            $forecast->wind = $weatherInfo["wind" . $i];
            
            // special handling for wind.
            $lessthanPos = strpos($forecast->wind, "小于");
            if($lessthanPos != false) {
                $forecast->wind = str_replace("小于", "<", $forecast->wind);
            }
            
            $changePos = strpos($forecast->wind, "转");
            if($changePos != false) {
                $first = substr($forecast->wind, 0, $changePos);
                $second = substr($forecast->wind, $changePos + 3);
                
                $forecast->wind = $first;
                
                if(strpos($first, "级") === false) {
                    $fengPos = strpos($second, "风");
                    if($fengPos != false) {
                        $secondLevel = substr($second, $fengPos + 3);
                        $forecast->wind = $forecast->wind . $secondLevel;
                    }
                }
            }
            
            // image path
            $imageIndex = $i * 2 - 1;
            if($i == 1){
                $forecast->dayPicUrl = sprintf($imagePath, $weatherInfo["img" . $imageIndex]);
            }
            else {
                $forecast->dayPicUrl = sprintf($imagePath, $weatherInfo["img" . $imageIndex]);
            }
            
            array_push ( $this->forecastArray, $forecast );
        }
    }
	
	private function init_weather_guide($weatherInfo)
	{
        if(is_null($weatherInfo))
		    return;
        
		//$guideArray
		$this->guideArray = array ();
		
        $guideItem = new Guide();
		$guideItem->title = "洗车";
		$guideItem->guide = $weatherInfo->index_xc;
		$guideItem->picUrl = "res/guide/i_w_wcar.png";
		array_push ( $this->guideArray, $guideItem );
		
		$guideItem = new Guide();
		$guideItem->title = "防晒"; //紫外线
		$guideItem->guide = $weatherInfo->index_uv;
		$guideItem->picUrl = "res/guide/i_w_uv.png";
		array_push ( $this->guideArray, $guideItem );	

		$guideItem = new Guide();
		$guideItem->title = "穿衣";
		$guideItem->guide = $weatherInfo->index; // . ":" . $weatherInfo->index_d;
		$guideItem->picUrl = "res/guide/i_w_dress.png";
		array_push ( $this->guideArray, $guideItem );
				
		$guideItem = new Guide();
		$guideItem->title = "晨练";
		$guideItem->guide = $weatherInfo->index_cl;
		$guideItem->picUrl = "res/guide/i_w_msport.png";
		array_push ( $this->guideArray, $guideItem );
		
		$guideItem = new Guide();
		$guideItem->title = "晾晒";
		$guideItem->guide = $weatherInfo->index_ls;
		$guideItem->picUrl = "res/guide/i_w_hung.png";
		array_push ( $this->guideArray, $guideItem );
	}
    
    private function parse_temp ( $temp )
	{
		$splitIndex = stripos($temp, "~");
		
		if ($splitIndex > -1) {
			$low = trim( substr($temp, 0, stripos($temp, "℃")) );
			
			$high = trim( substr($temp, stripos($temp, "~") + 1) );
			$high = substr( $high, 0, stripos($high, "℃") );
		}
		else {
			$low = substr( $temp, 0, stripos($temp, "℃") );
			$high = $low;
		}
		
		return array (
				'low' => $low,
				'high' => $high
		);
	}
    
    private function is_municipality($cityid)
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
	
	public function toJSON()
	{
		return json_encode($this);
	}
}

?>