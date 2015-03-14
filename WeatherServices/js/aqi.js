$(function () {
    var o = location.href.match(/\d{9}/)[0], a = "http://d1.weather.com.cn/aqi_mobile/" + o + ".html";
    $.ajax(
    {
        type: "GET",
        url: a,
        dataType: "script",
        success: function () {
            $(".pm25").text(aqi.p.p1),
            $(".chouy").text(aqi.p.p4),
            $(".co").text(aqi.p.p7),
            $(".eyhl").text(aqi.p.p6),
            $(".pm10").text(aqi.p.p5),
            $(".eyhd").text(aqi.p.p3);
            var o = aqi.p.p2;
            console.log(o),
            $("#aqi .da").empty(),
            $("#aqi .da").text(o), "" == o ? ($(".da").text("暂无数据"),
            $(".lies").hide(),
            $(".op_pm25_des").empty())

            : (o > 0 && 50 > o ? ($aqiC = "优", $color = "#3bb64f", $tsContent = "空气很好，可以外出活动，呼吸新鲜空气，拥抱大自然！", $diannum = "0.32", $jiaobiaocolor = "0px;")

            : o >= 50 && 100 > o ? ($aqiC = "良", $color = "#ff9900", $tsContent = "空气好，可以外出活动，除极少数对污染物特别敏感的人群以外，对公众没有危害！", $diannum = "0.32", $jiaobiaocolor = "-10px;")
            
            : o >= 100 && 150 > o ? ($aqiC = "轻度污染", $color = "#ff6000", $tsContent = "空气一般，老人、小孩及对污染物比较敏感的人群会感到些微不适！", $diannum = "0.32", $jiaobiaocolor = "-20px;")
            
            : o >= 150 && 200 >= o ? ($aqiC = "中度污染", $color = "#f61c1c", $tsContent = "空气较差，老人、小孩及对污染物比较敏感的人群会感到不适！", $diannum = "0.32", $jiaobiaocolor = "-30px;")
            
            : o > 200 && 300 >= o ? ($aqiC = "重度污染", $color = "#bb002f", $tsContent = "空气差，适当减少外出活动，老人、小孩出门时需做好防范措施！", $diannum = "0.3", $jiaobiaocolor = "-40px;")
            
            : o > 300 && ($aqiC = "严重污染", $color = "#7e0808", $tsContent = "空气很差，尽量不要外出活动!", $diannum = "0.26", $jiaobiaocolor = "-50px;"),

            $("<span class='da' style=color:" + $color + "></span><span class='mi' style=color:" + $color + ">" + $aqiC + "</span>").appendTo("#aqi"),
            $(".op_pm25_des").html("<strong>温馨提示:</strong><br />" + $tsContent), 
            $("#jiaobiao").html("<i class='op_pm25_i op_pm25_leveli3'  style='left:" + o * $diannum + "%;background-position:" + $jiaobiaocolor + " 0px'></i>"))
        }
    })
});