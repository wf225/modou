<?php
include_once 'Helper.php';
include_once 'WeatherClass.php';
include_once 'AqiHelper.php';

class YahooWeather extends IMWeather {

    static function fetch_forecast($cityId) {
		$domain = AppKeys::SEA_DOMAIN_WEATHER;
		$filename = sprintf(AppKeys::WEATHER_FILE, $cityId);
		$interval = AppKeys::INTERVAL_WEATHER_NOW;
        $target_url = sprintf(AppKeys::YAHOO_URL, $cityId);
		
		return Helper::fetch_data_to_sae($domain, $filename, $interval, $target_url);
	}
    
	function __construct($cityName) {
        
        $cityId = $this->get_city_code($cityName);
        $this->cityId = $cityId;
        $this->city = $cityName;
        
        $this->date = date("Y-m-d H:i:s");

        $obj = YahooWeather::fetch_forecast($cityId);
        if(!is_null($obj))
        {
            $this->file_time = $obj->file_time;
            $this->init_weather($obj->data);
        }
        
        //AQI
        $this->aqiArray = AqiHelper::read_aqi($cityName);
	}
    
    private function init_weather($data)
    {
        if(is_null($data))
		    return;
        
        $dom = new DOMDocument ();
        $yweather = "http://xml.weather.yahoo.com/ns/rss/1.0"; // 命名空间

        $dom->loadXML($data);
		$location = $dom->getElementsByTagNameNS($yweather, "location");
		if ($location != null && $location->length > 0)
        {
			//$this->city = $location->item(0)->attributes->item(0)->nodeValue;
			
            //atmosphere
			$atmosphere = $dom->getElementsByTagNameNS($yweather, "atmosphere"); // 大气
			$this->humidity = $atmosphere->item(0)->attributes->item(0)->nodeValue . "%"; // 获取湿度
			$this->visibility = $atmosphere->item(0)->attributes->item(1)->nodeValue;
			$this->pressure = $atmosphere->item(0)->attributes->item(2)->nodeValue;
			
            //wind
			$wind = $dom->getElementsByTagNameNS($yweather, 'wind'); // wind
			$windDirection = $wind->item(0)->attributes->item(1)->nodeValue;
            $windSpeed = $wind->item(0)->attributes->item(2)->nodeValue;
			$this->wind = $this->init_wind_direction(floatval($windDirection), floatval($windSpeed));
			
            //condition
			$condition = $dom->getElementsByTagNameNS($yweather, 'condition');
			$code = $condition->item(0)->attributes->item(1)->nodeValue; // 获取天气代码
			$this->temp = $condition->item(0)->attributes->item(2)->nodeValue; // 获取温度
			$this->picUrl =  $this->get_image_url( $code);
            $this->weather = $this->init_code_meaning($code);
			
            //astronomy
			$astronomy = $dom->getElementsByTagNameNS($yweather, 'astronomy');
			$this->sunrise = $astronomy->item(0)->attributes->item(0)->nodeValue;
			$this->sunset = $astronomy->item(0)->attributes->item(1)->nodeValue;
			
            //pubDate
			$this->update_time = $dom->getElementsByTagName('pubDate')->item(0)->nodeValue;			
			$this->week = Helper::getChineseWeekOfToday ();
			
            //yweather:forecast 
			$forecastNodes = $dom->getElementsByTagNameNS($yweather, 'forecast');
			$this->forecastArray = array ();
			foreach($forecastNodes as $node)
            {
				$forecast = new Forecast ();
                
				$forecast->week = $node->attributes->item(0)->nodeValue;
				$forecast->week = Helper::getChineseWeek($forecast->week);
				
				$forecast->low = $node->attributes->item(2)->nodeValue;
				$forecast->high = $node->attributes->item(3)->nodeValue;
                $code = $node->attributes->item(5)->nodeValue;
				$forecast->weather = $this->init_code_meaning($code); //$node->attributes->item (4)->nodeValue;
				
				$code = $node->attributes->item(5)->nodeValue;
				$forecast->dayPicUrl = $this->get_image_url( $code);
				//$forecast->nightPicUrl = $this->get_image_url( $code);

				array_push($this->forecastArray, $forecast);
			}
		}
    }
    
    function get_city_code( $city )
    {
        $filename = "yahoo_city_code.txt";
    	if(file_exists($filename)){
			
			$str = file_get_contents($filename);
			//$obj = json_decode ( $json );
            $arr = explode("\r\n",$str);
            foreach ($arr as $itemStr)
            {
            	$itemArr = explode(" ",$itemStr);
                if($itemArr[0] === $city)
                {
                    return $itemArr[1];   
                }
            }
            
            return "CHXX0116"; //if not found, return shanghai
        }
    }
    

	function init_wind_direction($direction, $speed) {
		$unit = 180 / 8;
        $result;
        
		if ($unit < $direction && $direction < $unit * 3) {
			$result = "西南";
		} else if ($unit * 3 <= $direction && $direction <= $unit * 5) {
			$result = "南";
		} else if ($unit * 5 < $direction && $direction < $unit * 7) {
			$result = "东南";
		} else if ($unit * 7 <= $direction && $direction <= $unit * 9) {
			$result = "东";
		} else if ($unit * 9 < $direction && $direction < $unit * 11) {
			$result = "东北";
		} else if ($unit * 11 <= $direction && $direction <= $unit * 13) {
			$result = "北";
		} else if ($unit * 13 < $direction && $direction < $unit * 15) {
			$result = "西北";
		} else {
			$result = "东";
		}
        
        return $result . "风 " . (int)$speed ."km/h";
	}
	
	function get_image_url($code)
	{
		return $code . ".gif";
	}
    
    function init_code_meaning($code)
    {
        $code_meaning = array ();
        $code_meaning[0] = "龙卷风";
        $code_meaning[1] = "热带风暴";
        $code_meaning[2] = "飓风";
        $code_meaning[3] = "强烈雷暴";
        $code_meaning[4] = "雷暴天气";
        $code_meaning[5] = "雨夹雪";
        $code_meaning[6] = "冻雨";
        $code_meaning[7] = "冰雹夹雪";
        $code_meaning[8] = "冻毛雨";
        $code_meaning[9] = "雾雨";
        $code_meaning[10] = "冻雨";
        $code_meaning[11] = "小雨";
        $code_meaning[12] = "阵雨";
        $code_meaning[13] = "阵雪";
        $code_meaning[14] = "小阵雪";
        $code_meaning[15] = "高吹雪";
        $code_meaning[16] = "雪";
        $code_meaning[17] = "冰雹";
        $code_meaning[18] = "冻雨";
        $code_meaning[19] = "灰尘天气";
        $code_meaning[20] = "有雾";
        $code_meaning[21] = "薄雾";
        $code_meaning[22] = "烟雾天气";
        $code_meaning[23] = "大风";
        $code_meaning[24] = "有风";
        $code_meaning[25] = "冷";
        $code_meaning[26] = "阴";
        $code_meaning[27] = "晚间多云";
        $code_meaning[28] = "白天多云";
        $code_meaning[29] = "晚间多云"; //晚间局部多云
        $code_meaning[30] = "白天多云"; //白天局部多云
        $code_meaning[31] = "晚间天晴";
        $code_meaning[32] = "晴朗";
        $code_meaning[33] = "晚间晴朗";
        $code_meaning[34] = "白天晴朗";
        $code_meaning[35] = "雨夹雹";
        $code_meaning[36] = "热";
        $code_meaning[37] = "局部阵雨"; //"局部风暴";
        $code_meaning[38] = "零星阵雨"; //"零星风暴";
        $code_meaning[39] = "零星阵雨"; //"零星风暴";
        $code_meaning[40] = "局部阵雨";
        $code_meaning[41] = "大雪";
        $code_meaning[42] = "零星阵雪";
        $code_meaning[43] = "大雪";
        $code_meaning[44] = "少云";
        $code_meaning[45] = "雷暴雨";
        $code_meaning[46] = "阵雪";
        $code_meaning[47] = "局部阵雨"; //"局部风暴";
        
        return $code_meaning[$code];
    }
}

?>