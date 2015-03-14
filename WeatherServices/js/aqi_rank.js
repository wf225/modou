//$(document).bind("mobileinit", function () {
//	$.mobile.loader.prototype.options.text = "Loading...";
//	$.mobile.loader.prototype.options.textVisible = true;
//	$.mobile.loader.prototype.options.theme = "a";
//	$.mobile.loader.prototype.options.html = "";
//});

$(document).on("pagebeforecreate", "#page_aqi_rank", function (e) {
	var url = "services/aqi";
	$.getJSON(url, get_aqi_rank_success);
});

function get_aqi_rank_success(data, status, xhr) {
	$("#page_aqi_rank #aqi_rank_list").html(
		$("#aqi_rank_listTemplate").render(data)
	);
}
