<?php
include_once 'Logger.php';

/**
 * 每天的天气预报状况
 */
class Forecast
{
	public $week; 			// week
	public $low; 			// low temperature
	public $high; 			// high temperature
	
	public $weather; 		// weather text
	public $wind;			// wind direction and speed
	public $dayPicUrl;
	public $nightPicUrl;
}

/**
 * 实时天气状况
 */
class Condition
{
    public $file_time; // 文件更新时间
    public $update_time; // 数据更新时间

	public $cityId;
	public $city;
	public $temp;		// current temperature
	public $weather; 	// weather
	public $wind;		// wind direction and speed
	public $picUrl;

	public $humidity;	//湿度
	public $visibility; //能见度
	public $pressure;	//压强
    
    public $aqiArray;
    
    function __construct($weatherInfo) {
        $this->cityId = $weatherInfo->cityid;
        $this->city = $weatherInfo->city;
        $this->temp = $weatherInfo->temp;
        $this->wind = sprintf("%s%s", $weatherInfo->WD, $weatherInfo->WS);
        $this->humidity = $weatherInfo->SD;
        $this->update_time = $weatherInfo->time;
    }
}

/**
 * 天气集合: 生活指数,未来预报, 当前天气,空气质量
 */
class IMWeather extends Condition
{    
	public $cityId;
	public $city;
	public $date;
	public $week;
    //public $lunar;
	public $sunrise;
	public $sunset;
    
	/**
     * 未来六天天气预报
	 */
	public $forecastArray;
    
    /**
     * 当前天气生活指数
     */
    public $guideArray;
    
    public $file_time; // 文件更新时间
    public $message; // 提示信息
}

/**
* 天气生活指数
*/
class Guide
{
	public $title;
	public $guide;
	public $picUrl;
	
	static function getAQI( $aqi )
	{
		$result;
		if(0 <= $aqi && $aqi <= 50) {
			$result = "优";
		} else if(51 <= $aqi && $aqi <= 100) {
			$result = "良";
		} else if(101 <= $aqi && $aqi <= 150) {
			$result = "轻度";
		} else if(151 <= $aqi && $aqi <= 200) {
			$result = "中度";
		} else if(201 <= $aqi && $aqi <= 300) {
			$result = "重度";
		}else if(300 <= $aqi) {
			$result = "严重";
		} else {
			$result = "Unknown";	
		}
		return $result . $aqi;
	}
}

/**
 * 空气质量
*/
class AQI  {
	/* 空气质量指数级别（状况）及表示颜色,注意将 RGB --> BGR
	 0 ~ 50		一级（优）  		green 	0x00FF00
	 51 ~ 100	二级（良）		yellow  0x00FFFF
	 101 ~ 150	三级（轻度污染） orange  0x25C1FF
	 151 ~ 200	四级（中度污染） red	 0x0000FF
	 201 ~ 300	五级（重度污染） purple  0xFF66E0
	 > 300		六级（严重污染） brown	 0x3F85CD
	*/
    
	public $area;
	public $aqi;
	public $co;
	public $co_24h;
	public $no2;
	public $no2_24h;
	public $o3;
	public $o3_24h;
	public $o3_8h;
	public $o3_8h_24h;
	public $pm10;
	public $pm10_24h;
	public $pm2_5;
	public $pm2_5_24h;
	public $quality;
	public $level;
	public $so2;
	public $so2_24h;
	public $primary_pollutant;
	public $time_point;
    
    //public $pm25_fgcolor;
    //public $pm25_bgcolor;
	
	public $file_time; // 文件更新时间
}

/**
 * 用户访问日志
*/
class User_access_log  {
    public $id;
    public $mac_address;
    public $ip;
    public $cityId;
    public $count;
    public $last_time;
}

?>