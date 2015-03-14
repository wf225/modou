<?php
include_once "libs/NotORM.php";
include_once 'Logger.php';
include_once 'WeatherClass.php';

class Helper {
    
    const IS_LOCAL = false;
	
	public static function getChineseDay($index) {
		$weekarray = array (
				"日",
				"一",
				"二",
				"三",
				"四",
				"五",
				"六" 
		);
		return "周" . $weekarray [( int ) $index]; // return "星期" . $weekarray [date ( "w" )];
	}

	public static function getChineseWeekOfToday() {
		return Helper::getChineseDay ( date ( "w" ) );
	}

	public static function getChineseWeek($eweek) {
		switch ($eweek) {
			case "Mon" :
				return Helper::getChineseDay ( 1 );
			case "Tue" :
				return Helper::getChineseDay ( 2 );
			case "Wed" :
				return Helper::getChineseDay ( 3 );
			case "Thu" :
				return Helper::getChineseDay ( 4 );
			case "Fri" :
				return Helper::getChineseDay ( 5 );
			case "Sat" :
				return Helper::getChineseDay ( 6 );
			case "Sun" :
				return Helper::getChineseDay ( 0 );
			default :
				return "undefine";
		}
	}
	
	public static function get_url_content($url) { 
		if(function_exists('file_get_contents')) {
			$ctx = stream_context_create(array( 
				'http' => array( 
							'timeout' => 5 //等待3秒
							) 
						) 
					);

			//$file_contents = file_get_contents($url, 0, $ctx);
			$file_contents = file_get_contents($url);
		}
		else 
		{
			$ch = curl_init(); 
			$timeout = 5;
			
			$this_header = array( "content-type: text/json;charset=UTF-8" );

			curl_setopt ($ch,CURLOPT_HTTPHEADER, $this_header);
			curl_setopt ($ch, CURLOPT_URL, $url); 
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 
			$file_contents = curl_exec($ch); 
			
			$getinfo = curl_getinfo($ch);	
			curl_close($ch);
		} 
		
		return $file_contents; 
	}
	
	public static function today()
	{
		date_default_timezone_set ("Asia/Shanghai");
		$a=date("Y");
		$b=date("m");
		$c=date("d");
		$d=date("G");
		$e=date("i");
		$f=date("s");
		return $a.'年'.$b.'月'.$c.'日'.$d.'时'.$e.'分'.$f.'秒';
	}
	
    /**
     * Fetch the data to sae storage, and return an object.
     */
	public static function fetch_data_to_sae($domain, $filename, $interval, $target_url)
	{
		if(Helper::IS_LOCAL){
            return Helper::fetch_data_to_local($filename, $interval, $target_url);
        }
		
        Logger::output("fetch_data_to_sae: " . $filename);
		$storage = new SaeStorage();
		
		$currentTime = date("Y-m-d H:i:s");
		Logger::output($currentTime . $filename);

		// 1. check the exsiting file's modify date, if <= interval then return it.
		if($storage->fileExists($domain, $filename)){
			
			$json = $storage->read($domain, $filename);
			$obj = json_decode ( $json );
			
            ///==
			// AQI will not update during 21:00 to 6:00.
        	$time = date("H:i:s");
			if($filename === AppKeys::AQI_RANK_FILE
				&& (strtotime($time) > strtotime(AppKeys::AQI_END_TIME) || strtotime($time) < strtotime(AppKeys::AQI_START_TIME)))
			{
				return $obj;
			}
			
			// Else, update by interval
			$minute = floor((strtotime($currentTime) - strtotime($obj->file_time)) % 86400 / 60);
            //echo $json;
            if( $minute <= $interval) 
            {
                return $obj;
			}
            else
            {
				$storage->delete($domain, $filename);  // remove the old file
			}
		}
		
		// 2.retget json from service
        $json = Helper::get_url_content ( $target_url );
        //$obj = json_decode ( $json );
        $obj = $json;
		
		if(!is_null($obj))
		{
            $newObj = array(
                    "file_time" => $currentTime,
                    "data" => $obj,
                );

            $json = json_encode($newObj);
			// create a new file			
            $storage->write($domain, $filename, $json);
            ///==
            
            $obj = json_decode($json);
            
            // saveDb_aqi_ranking
            if($filename === AppKeys::AQI_RANK_FILE)
            {               
                Helper::saveDb_aqi_ranking($obj);
            }
            else if(Helper::endWith($filename, "_now.json"))
            {
                Helper::saveDb_weather_condition($obj);
            }
            
            // MUST return an object.
			return $obj;
		}
				
		return null;
	}
	
    /**
     * Fetch the data to local, and return an object.
     */
	public static function fetch_data_to_local($filename, $interval, $target_url) 
	{
		$currentTime = date("Y-m-d H:i:s");
        
		// 1. check the exsiting file's modify date, if <= interval then return it.	
		if(file_exists($filename)){
			
			$json = file_get_contents($filename);
			$obj = json_decode ( $json );
			
            // AQI will not update during 21:00 to 6:00.
            $time = date("H:i:s");
            if($filename === AppKeys::AQI_RANK_FILE
                && (strtotime($time) > strtotime(AppKeys::AQI_END_TIME) || strtotime($time) < strtotime(AppKeys::AQI_START_TIME)))
            {
                return $obj;
            }
			
            // Else, update by interval
            $minute = floor((strtotime($currentTime)-strtotime($obj->file_time)) % 86400 / 60);
            if( $minute <= $interval)
            {
                return $obj;
            }
            else
            {
                unlink($filename); // remove the old file
            }
		}
		
		// 2.retget json from service
		$json = Helper::get_url_content ( $target_url );
		//$obj = json_decode ( $json );
        $obj = $json;
		
		if(!is_null($obj))
		{
            $newObj = array(
                    "file_time" => $currentTime,
                    "data" => $obj,
                );

            $json = json_encode($newObj);
            // create a new file
            file_put_contents($filename, $json, LOCK_EX);
            
            $obj = json_decode($json);
            
            // saveDb_aqi_ranking
            if($filename === AppKeys::AQI_RANK_FILE)
            {               
                Helper::saveDb_aqi_ranking($obj);
            }
            //else if(Helper::endWith($filename, "_now.json"))
            //{
            //    Helper::saveDb_weather_condition($obj);
            //}
            
            // MUST return an object.
			return $obj;
		}
				
		return null;
	}
	
    // String ------------------
	public static function startWith($str, $needle) {
		return strpos($str, $needle) === 0;
	}

    public static function endWith($hay, $needle) {
        return substr($hay, -strlen($needle)) === $needle;
    }
    
    // MYSQL ------------------   
    public static function localConnection(){
        $dsn = "mysql:host=localhost;dbname=app_modouweather";
        $username = "root";
        $password = "root";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);    
        $pdo->exec('set names utf8');
        
        //$db = new NotORM(Helper::$pdo);
        return $pdo;
    }
    
    /**
     * 主库: for write
     */
    public static function mainConnection(){
        if(Helper::IS_LOCAL){
            return Helper::localConnection();
        }
        
        $dsn = sprintf("mysql:host=%s:%s;dbname=%s", AppKeys::MYSQL_HOST_M, AppKeys::MYSQL_PORT, AppKeys::MYSQL_DB);
        $pdo = new PDO($dsn, AppKeys::MYSQL_USER, AppKeys::MYSQL_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);    
        $pdo->exec("SET NAMES utf8");
        
        return $pdo;
    }
    
    /**
     * 从库: for read
     */
    public static function slaveConnection(){
        if(Helper::IS_LOCAL){
            return Helper::localConnection();
        }
        
        $dsn = sprintf("mysql:host=%s:%s;dbname=%s", AppKeys::MYSQL_HOST_S, AppKeys::MYSQL_PORT, AppKeys::MYSQL_DB);
        $pdo = new PDO($dsn, AppKeys::MYSQL_USER, AppKeys::MYSQL_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);    
        $pdo->exec("SET NAMES utf8");
        $pdo->exec("SET CHARACTER SET utf8");
        
        return $pdo;
    }
    
    public static function saveDb_aqi_ranking($obj) {
        if(is_null($obj) || is_null($obj->data))
			return null;
		
        $dataArray = $obj->data;
        if(!is_array($data)) {
            $dataArray = json_decode($obj->data);
        }
        if(!is_array($dataArray)) {
            return null;
        }
        
        $result = array();
        foreach ( $dataArray as $item ) {
            $item->file_time = $obj->file_time;
            $json = json_encode($item);            
            $objArray = json_decode($json, true);
            array_push ( $result, $objArray );
        }

        try{        
            $pdo = Helper::mainConnection();
            $db = new NotORM($pdo);
            $table = $db->aqi_ranking();
            
            $affected  = $table->insert_multi($result);
            $pdo = null; //断开连接  
        }
        catch(PDOException $e){  
            Logger::error("PDO Failed:".$e->getMessage());   
        }
    }
    
    public static function saveDb_weather_condition($obj) {
        if(is_null($obj))
            return null;
		
        $coondition = new Condition($obj->data->weatherinfo);
        $coondition->file_time = $obj->file_time;
        $update_time = strtotime($coondition->update_time);
        $coondition->update_time = date("Y-m-d H:i:s", $update_time);
        
        $json = json_encode($coondition);            
        $objArray = json_decode($json, true);
        unset($objArray["aqiArray"]);

        try{        
            $pdo = Helper::mainConnection();
            $db = new NotORM($pdo);
            $table = $db->weather_condition();
            
            $affected  = $table->insert($objArray);
            $pdo = null; //断开连接  
        }
        catch(PDOException $e){  
            Logger::error("PDO Failed:".$e->getMessage());   
        }
    }
    
    static function save_user_access_log($userLog) {
        try{        
            $pdo = Helper::mainConnection();
            $db = new NotORM($pdo);
            
            $datarow = $db->user_access_log("mac_address = '" . $userLog->mac_address . "'")->fetch();
            $userLog->last_time = date('Y-m-d H:i:s');
            
            if($datarow === false)
            {
                $userLog->count = 1;
                
                $json = json_encode($userLog);            
                $objArray = json_decode($json, true);
                
                $table = $db->user_access_log();
                $affected  = $table->insert($objArray);
            } 
            else 
            {                
                $count = (int)$datarow["count"];
                $datarow["count"] = $count + 1;
                $datarow["cityId"] = $userLog->cityId;
                $datarow["last_time"] = $userLog->last_time;
                $affected  = $datarow->update();
            }
            
            $pdo = null; //断开连接  
        }
        catch(PDOException $e){  
            Logger::error("PDO Failed:".$e->getMessage());   
        }
    }
    
    public static function guide_item($title, $guide, $bgcolor = 0xEDEDED, $fgcolor = 0xE16F00)
    {
        return array(
            "title" => $title, 
            "guide" => $guide,
            "bgcolor" => $bgcolor,
            "fgcolor" => $fgcolor
        );
    }
    
    
    public static function get_aqi_color($result, $aqi)
    {
        $aqi_bgcolor;
        if($aqi <= 50) {
            $aqi_bgcolor = 0x00FF00;
        }
        else if($aqi <= 100) {
            $aqi_bgcolor = 0x00FFFF;
        }
        else if($aqi <= 150) {
            $aqi_bgcolor = 0x25C1FF;
        }
        else if($aqi <= 200) {
            $aqi_bgcolor = 0x0000FF;
        }
        else if($aqi <= 300) {
            $aqi_bgcolor = 0xFF66E0;
        }
        else if($aqi > 300) {
            $aqi_bgcolor = 0x3F85CD;
        }
        
        return $aqi_bgcolor;
    }
    
}

?>