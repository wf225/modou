// overrides to jQM's default settings.
$(document).bind("mobileinit", function () {
    $.mobile.loader.prototype.options.text = "Loading...";
    $.mobile.loader.prototype.options.textVisible = true;
    $.mobile.loader.prototype.options.theme = "a";
    $.mobile.loader.prototype.options.html = "";
});

// pagebeforecreate
$(document).on("pagebeforecreate", "#page_weather", function (e) {

    var url = $.mobile.path.parseUrl(e.target.baseURI);
    var cityid = getQueryString(url, "cityid");

    if (cityid == null) {
        cityid = 101020100;
    }
    var url = "services/weather/" + cityid;
    $.getJSON(url, get_weather_success);
});

function get_weather_success(data, status, xhr) {

    $("#page_weather #weatherToday").html(
		$("#weatherTodayTemplate").render(data)
	);

    $("#page_weather #weatherList").html(
		$("#weatherTemplate").render(data)
	);

    $("#page_weather #guideList").html(
		$("#guideTemplate").render(data.guideArray)
    );

    $("#page_weather #weatherNow").html(
		$("#weatherNowTemplate").render(data)
	);

    if (data.aqiArray != null) {
        $("#page_weather #aqi").html(
		    $("#aqiTemplate").render(data.aqiArray)
	    );

        $("#page_weather #aqiList").html(
		    $("#aqiListTemplate").render(data.aqiArray)
	    );
    }
}

$.views.tags({
    tag_aqi_num: function (aqi, quality) {
        //aqi = 300;
        var pm25_bgcolor = "#000000";
        var tsContent;
        var diannum;
        var jiaobiaocolor;

        if (aqi <= 50) {
            pm25_bgcolor = "#43ce17";
            tsContent = "空气很好，可以外出活动，呼吸新鲜空气！";
            diannum = "0.32";
            jiaobiaocolor = "0px;";
        }
        else if (aqi <= 100) {
            pm25_bgcolor = "#efdc31";
            tsContent = "空气好，可以外出活动，除极少数对污染物特别敏感的人群会微感不适！"
            diannum = "0.32";
            jiaobiaocolor = "-10px;";
        }
        else if (aqi <= 150) {
            pm25_bgcolor = "#fa0";
            tsContent = "空气一般，老人、小孩及对污染物比较敏感的人群会微感不适！"
            diannum = "0.32";
            jiaobiaocolor = "-20px;";
        }
        else if (aqi <= 200) {
            pm25_bgcolor = "#ff401a";
            tsContent = "空气差，老人、小孩及对污染物比较敏感的人群会感到不适！"
            diannum = "0.32";
            jiaobiaocolor = "-30px;";
        }
        else if (aqi <= 300) {
            pm25_bgcolor = "#d20040";
            tsContent = "空气较差，适当减少外出活动，老人、小孩出门时需做好防范措施！"
            diannum = "0.30";
            jiaobiaocolor = "-40px;";
        }
        else if (aqi > 300) {
            pm25_bgcolor = "#9c0a4e";
            tsContent = "空气很差，尽量不要外出活动!"
            diannum = "0.26";
            jiaobiaocolor = "-50px;";
        }

        var template = "<span class='da' style='color:{0}'>{1} {2}</span>"
        var msg = String.format(template, pm25_bgcolor, aqi, quality);

        //$(".op_pm25_des").html("<strong>温馨提示:</strong><br />" + tsContent);
        $(".op_pm25_des").html(tsContent);
        $("#jiaobiao").html("<i class='op_pm25_i op_pm25_leveli3'  style='left:" + aqi * diannum + "%;background-position:" + jiaobiaocolor + " 0px'></i>")

        return msg;
    }
});

String.format = function (src) {
    if (arguments.length == 0) return null;
    var args = Array.prototype.slice.call(arguments, 1);
    return src.replace(/\{(\d+)\}/g, function (m, i) {
        return args[i];
    });
};

// default: urlObj = window.location
function getQueryString(urlObj, name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
    var r = urlObj.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]); return null;
}

