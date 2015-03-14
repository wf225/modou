<?php
include_once "libs/NotORM.php";
include_once 'Logger.php';
include_once 'WeatherClass.php';

class AqiHelper  {
	/* 空气质量指数级别（状况）及表示颜色,注意将 RGB --> BGR
	 0 ~ 50		一级（优）  		green 	0x00FF00
	 51 ~ 100	二级（良）		yellow  0x00FFFF
	 101 ~ 150	三级（轻度污染） orange  0x25C1FF
	 151 ~ 200	四级（中度污染） red	 0x0000FF
	 201 ~ 300	五级（重度污染） purple  0xFF66E0
	 > 300		六级（严重污染） brown	 0x3F85CD
	*/
	
	public static function fetch_aqi_rank() {
		$domain = AppKeys::SEA_DOMAIN_PM25;
		$interval = AppKeys::INTERVAL_PM25; // 30 minutes
		$target_url = sprintf(AppKeys::AQI_RANK_URL, AppKeys::PM25IN_APPKEY); 
		
		$json = Helper::fetch_data_to_sae($domain, AppKeys::AQI_RANK_FILE, $interval, $target_url);
        return $json;
	}
	
    /* $aqi = new AQI() */
    public static function read_aqi($cityName) {
		$jsonObj = AqiHelper::fetch_aqi_rank();
		if(is_null($jsonObj) || is_null($jsonObj->data))
			return null;
		
        $dataArray = $jsonObj->data;
        if(!is_array($dataArray)) {
            $dataArray = json_decode($jsonObj->data);
        }
        if(!is_array($dataArray)) {
            return null;
        }
        //var_dump(json_decode($data));
        
		foreach ( $dataArray as $item )
        {
			if($item->area == $cityName
                || Helper::startWith($item->area, $cityName)
                || $item->area == $cityName . "市" )
            {
                $aqi = new AQI();
				$aqi->aqi			= $item->aqi;
				$aqi->area			= $item->area;
				$aqi->co			= $item->co;
				$aqi->co_24h		= $item->co_24h;
				$aqi->no2			= $item->no2;
				$aqi->no2_24h		= $item->no2_24h;
				$aqi->o3			= $item->o3;
				$aqi->o3_24h		= $item->o3_24h;
				$aqi->o3_8h			= $item->o3_8h;
				$aqi->o3_8h_24h		= $item->o3_8h_24h;
				$aqi->pm10			= $item->pm10;
				$aqi->pm10_24h		= $item->pm10_24h;
				$aqi->pm2_5			= $item->pm2_5;
				$aqi->pm2_5_24h		= $item->pm2_5_24h;
				$aqi->quality		= $item->quality;
				$aqi->level			= $item->level;
				$aqi->so2			= $item->so2;
				$aqi->so2_24h		= $item->so2_24h;
				$aqi->time_point	= substr($item->time_point, 11, 5);
				$aqi->primary_pollutant = $item->primary_pollutant;
				$aqi->file_time	= $jsonObj->file_time;
				
                //return $aqi;
                $json = json_encode($aqi);            
            	$objArray = json_decode($json, true);
                return $objArray;
			}
		}
		
		return null;
	}
    
    // query all aqi from aqi_rank.
    public static function query_all_aqi() {
        try{
            $pdo = Helper::slaveConnection();
            $db = new NotORM($pdo);
            // select the last updated datalist.
            $table = $db->aqi_ranking()->order("file_time DESC")->limit(AppKeys::AQI_RANK_COUNT);
            $datarow = $table; // get all data            
            
            $result = array();
            foreach($datarow as $key=>$value)
            {
                $subItem = array();
                foreach($value as $subKey=>$subValue)
                {
                    //echo sprintf("%s=>%s\n", $subKey, $subValue);
                    $subItem[$subKey] = $subValue;
                }
                
                array_push($result, $subItem);
            }
            
            $pdo = null; //断开连接
            return $result; // MUST return a object or array.
            
        }
        catch(PDOException $e){  
            Logger::error("PDO Failed:".$e->getMessage());   
        }
    }
    
    // query aqi from aqi_rank.
    public static function query_aqi($cityName) {
        try{
            $pdo = Helper::slaveConnection();
            $db = new NotORM($pdo);
            //$table = $db->aqi_ranking();
            
            // select the last updated data.
            $datarow = $db->aqi_ranking("area LIKE ?", $cityName . "%")->order("file_time DESC")->fetch();
            if($datarow === false)
                return null;
            
            $result = array();
            foreach($datarow as $key=>$value)
            {
                //echo sprintf("%s=>%s\n", $key, $value);
                $result[$key] = $value;
            }
            
            $result["time_point"] = date("H:i", strtotime($result["time_point"]));

            $pdo = null; //断开连接
            return $result; // MUST return a object or array.
            
        }
        catch(PDOException $e){  
            Logger::error("PDO Failed:".$e->getMessage());   
        }
    }    
 
}

?>