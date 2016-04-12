#!/bin/sh
# version=0.8.2

. /lib/functions/app.sh 

CURDIR=$(cd $(dirname $0) && pwd)
APP_WCLOCK="$CURDIR/bin/wclock"
PIDFILE="$CURDIR/conf/custom.pid"

CITYID=""
URL_WEATHER="http://modouweather.sinaapp.com/services/weather"
PATH_WEATHER=$CURDIR/"data/weather_info.json"
DATAJSON="$CURDIR/conf/data.json"
SETCONF="$CURDIR/data/customset.conf"
#CRON="/data/conf/cron.d/matrix"
CRON="/etc/crontabs/matrix"

interval=1s;
timeout=10;
loopcount=0;
wait_download()
{
	$loopcount = 0;
	while [ ! -f "$1" ] && $loopcount < $timeout
	do
		echo "wait for downloading..."
		sleep $interval
		$loopcount += 1;
	done
}

download()
{
	CITYID=`/system/sbin/json4sh.sh "get" $DATAJSON city_name  value`
	echo $CITYID
	echo $! > $PIDFILE
	
	# 1. display the wclock at first
	if [ -f "$PATH_WEATHER" ]; then 
		echo "remove PATH_WEATHER"
		rm -f "$PATH_WEATHER"
	fi

	# 2. get weather info form server
	SN=`sn_get snmac`
	URL_WEATHER="$URL_WEATHER/$CITYID/$SN"

	printf '%10s %s\n' SN $SN
	printf '%10s %s\n' MAC $MAC
	printf '%10s %s\n' URL_WEATHER "$URL_WEATHER"

	wget -q "$URL_WEATHER" -O "$PATH_WEATHER"

	# 3. download complete, show the new info
	wait_download "$PATH_WEATHER"
	ls -l $PATH_WEATHER

        logger "download weather"
}

start()
{	
	start_loading 正在获取天气信息
	download;
	finish_loading

	if [ -f "$PATH_WEATHER" ]; then
		echo "show new weather"
		
		#设置自动更新：每30分钟一次
		#sed -i -e '$a\*/2 \* \* \* \* '$CURDIR'/modouweather.sh download >/dev/null' $CRON
		#/system/sbin/crond.sh restart

        echo "*/5 * * * * $CURDIR/modouweather.sh download 1>/dev/null 2>&1 &" >> $CRON
		

		$APP_WCLOCK
		
		#程序退出后删除
		sed -i '/modouweather.sh download/d' $CRON
		#/system/sbin/crond.sh restart
	else
		echo "exit";
		exit 0;	
	fi
}

stop()
{
    killall wclock 1>/dev/null 2>&1;
    pid=`cat $PIDFILE 2>/dev/null`;
    kill -SIGUSR1 $pid >/dev/null 2>&1;
	
	rm -f $CRON_BAK
	
    return 0;
}

case "$1" in
    "start")
        start;
        exit 0;
        ;;
    "stop")
        stop;
        exit 0;
        ;;
    "download")
        download;
        exit 0;
        ;;	
    *)
        exit 0;
        ;;
esac
