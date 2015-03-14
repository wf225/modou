#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <sys/time.h>
#include <sys/stat.h>
#define MWINCLUDECOLORS
#include "nano-X.h"
#include "cJSON.h"

#define CWIDTH		320		/* Max 320 */
#define CHEIGHT		240		/* Max 240 */
#define HEADER_HEIGHT		30
#define GUIDE_BGWIDTH		100
#define GUIDE_BGHEIGHT		20
#define ROWSPAN				22 //行宽

#define BODY_FGCOLOR		BLUE //BLUE		/* YELLOW BLUE GREEN WHITE MAGENTA */
#define BODY_BGCOLOR		WHITE
#define HEADER_BGCOLOR		0xCD6600 //BLUE
#define HEADER_FGCOLOR		YELLOW
#define SPLIT_FGCOLOR 		0xEDEDED //GRAY
#define TIME_FGCOLOR		0xE16F00 //E16F00, 005AFF
#define GUIDE_BGCOLOR		0xEDEDED

#define PATH_LEN 		300
#define FILE_LEN 		3000
#define PATH_FONT_LED	"res/TimeNumber-LED.ttf"
#define PATH_FONT_SYS	"mono.ttf"
#define PATH_WEATHER 	"data/weather_info.json"
#define PATH_WEATHERNOW "data/weather_info_now.json"
#define PROC_NAME		"bin/wclock"
#define INTERVAL_READ	60	

static  GR_WINDOW_ID 	wid_root;		/* id for root window */
static	GR_WINDOW_ID	wid_btn_close;	/* id for wid_btn_close */
static	GR_GC_ID	gc_body;		/* graphics context for text */
static	GR_GC_ID	gc_body_bg;		/* graphics context for rectangle */
static	GR_GC_ID	gc_header_bg;
static	GR_GC_ID	gc_header;
static	GR_GC_ID	gc_split;
static	GR_GC_ID	gc_time;
static	GR_GC_ID	gc_guide;
static	GR_GC_ID	gc_guide_bg;
static	GR_GC_ID 	gc_pm25;
static	GR_GC_ID 	gc_pm25_bg;
static	cJSON * 	json_weather = NULL;
static	cJSON * 	json_weather_now = NULL;
static	GR_BOOL		flag_second = GR_TRUE;
static  time_t 		t_start;

void init_gc(void); // init graphics context
GR_FONT_ID set_font(GR_GC_ID gc_body, GR_CHAR * fontname, int size);
void event_handler (GR_EVENT *event);
void do_exposure(GR_EVENT_EXPOSURE *ep);
void do_clock(void);
void renderUI(void);
int  Ymd2Wday(int year, int month, int days);

cJSON * read_weather(void);
void draw_weather(cJSON *root);
void draw_weatherItem(cJSON *weatherArray, int index, int offsetX, int offsetY);

cJSON * read_weather_now(void);
void draw_weatherNow(cJSON *root);

void draw_guideIndex(cJSON *guidArray, int index, int offsetX, int offsetY);
void draw_bgRect(int offsetX, int offsetY, int width, int height);
GR_IMAGE_INFO draw_image(int offsetX, int offsetY, char* imageUrl);

static char* wday[]={ "周日","周一","周二","周三","周四","周五","周六" };
//static char *wday[] = {"SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"};
static char path_proc[PATH_LEN];
static char	path_ledFont[PATH_LEN];
static char path_image[PATH_LEN];
static char path_weather_file[PATH_LEN];
static char path_weatherNow_file[PATH_LEN];


int main (int argc,char **argv)
{
	if (GrOpen() < 0)
	{
		fprintf (stderr, "GrOpen failed");
		exit (1);
	}

	// get current path.
	//getcwd(path_proc, PATH_LEN);
    char sysfile[15] = "/proc/self/exe";
    int  namelen = 256;
    char selfname[256];
    memset( selfname, 0, 256 );
    int rval = readlink( sysfile, selfname, namelen);
    if ( -1 != rval )
    {
        //printf( "selfname: %s\n", selfname );
		//printf( "rval: %d\n", rval );
		
	  	int last_slash = rval - strlen(PROC_NAME);	  	
	  	strncpy(path_proc, selfname, last_slash); // no substr in C.
	  	//printf( "last_slash: %d\n", last_slash );
	  	//printf( "path_proc: %s\n", path_proc );
	  
		// init file path.
		strcpy(path_ledFont, path_proc);
		strcat(path_ledFont, PATH_FONT_LED);

		strcpy(path_weather_file, path_proc);
		strcat(path_weather_file, PATH_WEATHER);
		
		strcpy(path_weatherNow_file, path_proc);
		strcat(path_weatherNow_file, PATH_WEATHERNOW);
		
		/*printf( "path_ledFont: %s\n", path_ledFont );
		printf( "path_weather_file: %s\n", path_weather_file );
		printf( "path_weatherNow_file: %s\n", path_weatherNow_file );
		*/
    }


	// init graphics context
	init_gc();
	
	// init windows
	wid_root = GrNewWindow(GR_ROOT_WINDOW_ID, 0, 0, CWIDTH, CHEIGHT, 0, BODY_BGCOLOR, BODY_BGCOLOR);
	//GR_WINDOW_INFO winfo;
	//GrGetWindowInfo(wid_root, &winfo);

	wid_btn_close = GrNewWindow(wid_root, 0, 0, HEADER_HEIGHT, HEADER_HEIGHT, 0, HEADER_BGCOLOR, HEADER_BGCOLOR);
	GrSelectEvents (wid_root, GR_EVENT_MASK_EXPOSURE | GR_EVENT_MASK_CLOSE_REQ);
	GrSelectEvents(wid_btn_close, GR_EVENT_MASK_BUTTON_DOWN | GR_EVENT_MASK_BUTTON_UP);

	GrMapWindow (wid_root);
	GrMapWindow(wid_btn_close);
	GrSetFocus(wid_root);

	GR_EVENT	event;		/* current event */
	while (1) 
	{
		GrGetNextEventTimeout(&event, 1000L);
		//GrGetNextEvent(&event);

		//printf("event->type: %d \n", (int)event.type); // NOTE: keep this to avoid bus error.
		switch (event.type)
		{
			case GR_EVENT_TYPE_BUTTON_DOWN: //2
				break;
				
			case GR_EVENT_TYPE_BUTTON_UP: //
				if (event.button.wid == wid_btn_close) {
					GrClose();
					exit(0);
				}
				break;	

			case GR_EVENT_TYPE_EXPOSURE: //1
				do_exposure(&event.exposure);
				break;

			case GR_EVENT_TYPE_CLOSE_REQ: //15
				GrClose();
				exit (0);
		
			case GR_EVENT_TYPE_TIMEOUT: //16
				do_exposure(&event.exposure);
				break;
		}
	}
}

void init_gc()
{
	// init graphics context
	gc_body = GrNewGC();
	GrSetGCForeground(gc_body, BODY_FGCOLOR);
	GrSetGCBackground(gc_body, BODY_BGCOLOR);

	gc_body_bg = GrNewGC();
	GrSetGCForeground(gc_body_bg, BODY_BGCOLOR);
	GrSetGCBackground(gc_body_bg, BODY_BGCOLOR);

	gc_header = GrNewGC();
	GrSetGCForeground(gc_header, HEADER_FGCOLOR);
	GrSetGCBackground(gc_header, HEADER_BGCOLOR);


	gc_header_bg = GrNewGC();
	GrSetGCForeground(gc_header_bg, HEADER_BGCOLOR);
	GrSetGCBackground(gc_header_bg, HEADER_BGCOLOR);
	
	gc_split = GrNewGC();
	GrSetGCForeground(gc_split, SPLIT_FGCOLOR);
	
	gc_time = GrNewGC();
	GrSetGCForeground(gc_time, TIME_FGCOLOR);
	GrSetGCBackground(gc_time, BODY_BGCOLOR);
	
	gc_guide = GrNewGC();
	GrSetGCForeground(gc_guide, BODY_FGCOLOR);
	GrSetGCBackground(gc_guide, GUIDE_BGCOLOR);
	
	gc_guide_bg = GrNewGC();
	GrSetGCForeground(gc_guide_bg, GUIDE_BGCOLOR);
	GrSetGCBackground(gc_guide_bg, GUIDE_BGCOLOR);
	
	gc_pm25 = GrNewGC();
	gc_pm25_bg = GrNewGC();
}

void do_exposure(GR_EVENT_EXPOSURE *ep)
{
	if (ep->wid == wid_root){
		renderUI();
	}
	else {
		//printf("ep->wid != wid_root.\n\n");
		return;
	}
}

void do_clock()
{
	renderUI();
}

void renderUI()
{
	//printf("\nstart renderUI\n");
	GR_FONT_ID	font;
	// draw header background.
	GrFillRect(wid_root, gc_header_bg, HEADER_HEIGHT, 0, CWIDTH - HEADER_HEIGHT, HEADER_HEIGHT);
	// draw body background.
	GrFillRect(wid_root, gc_body_bg, 0, HEADER_HEIGHT, CWIDTH, CHEIGHT);
	
	struct tm *tp;
	time_t t_now;
    time(&t_now);
    
    tp = localtime(&t_now); //取得当地时间

	int y,m,d,w;
	y = 1900+tp->tm_year;
	m = 1+tp->tm_mon;
	d = tp->tm_mday;
	w = Ymd2Wday(y,m,d);
	//printf("%d年%d月%d日是%s.\n\n", y, m, d, wday[w]);
	
	
	// re-read json data when file is updated.
	/*
	struct stat st;
	printf("path_weather_file:%s", path_weather_file);
	if(stat(path_weather_file, &st)==0)
	{
		printf("st.st_mtime = %s", st.st_mtime);
	}
	*/
	
	if(json_weather == NULL || json_weather_now == NULL 
		|| t_now - t_start > INTERVAL_READ)
	{
		if(json_weather != NULL) {
			cJSON_Delete(json_weather);
		}
		if(json_weather_now != NULL) {
			cJSON_Delete(json_weather_now);
		}
		
		json_weather = read_weather(); // read json file
		json_weather_now = read_weather_now(); // read json file
		
		// record the reading datetime.
		t_start = t_now;
	}

	// draw close button -----
	int arrowCenter_X = 10;
	int arrowCenter_Y = HEADER_HEIGHT / 2;
	int arrowOffset = HEADER_HEIGHT * 0.4;
	GrLine (wid_btn_close, gc_header, arrowCenter_X,arrowCenter_Y, arrowCenter_X + arrowOffset,arrowCenter_Y - arrowOffset);
	GrLine (wid_btn_close, gc_header, arrowCenter_X,arrowCenter_Y, arrowCenter_X + arrowOffset,arrowCenter_Y + arrowOffset);
	
	// draw split line
	int splitCenter_Y = HEADER_HEIGHT + (CHEIGHT - HEADER_HEIGHT) / 2 - 10;
	//int splitCenter_X = CWIDTH / 2;
	GrLine (wid_root, gc_split, GUIDE_BGWIDTH + 5,splitCenter_Y, CWIDTH,splitCenter_Y); //H
	//GrLine (wid_root, gc_split, splitCenter_X,HEADER_HEIGHT, splitCenter_X,CHEIGHT); //V

	// draw header date -----------
	char	datebuf[64];
	char	timebuf[64];
	char	secondbuf[64];
	int 	fontheight = 21;
	int 	offsetX = 35;
	int 	offsetY = 3;
	font = set_font(gc_header, PATH_FONT_SYS, fontheight);

	// draw date
	strftime(datebuf, sizeof(datebuf), "%Y-%m-%d", tp);
	GrText(wid_root, gc_header, offsetX, offsetY, datebuf, -1, GR_TFASCII|GR_TFTOP);
	// draw week
	GrText(wid_root, gc_header, offsetX + 140, offsetY, wday[w], 6, GR_TFUTF8|GR_TFTOP);
	// draw city
	char *city = cJSON_GetObjectItem(json_weather, "city")->valuestring;
	GrText(wid_root, gc_header, offsetX + 195, offsetY, city, strlen(city), GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font);

	// draw LED time -----------
	int local_hour = tp->tm_hour + 8;
	if(local_hour == 24) local_hour = 0;

	fontheight = 65;
	offsetX = 120; // 3;
	offsetY = HEADER_HEIGHT + 20;
	font = set_font(gc_time, path_ledFont, fontheight);
	
	if(flag_second)
	{
		strftime(timebuf, sizeof(timebuf), "%H:%M", tp);
	}
	else
	{
		strftime(timebuf, sizeof(timebuf), "%H %M", tp);
	}
	flag_second = !flag_second;
	//strftime(timebuf, sizeof(timebuf), "%H:%M", tp);
	
	GrText(wid_root, gc_time, offsetX, offsetY, timebuf, -1, GR_TFASCII|GR_TFTOP);
	GrDestroyFont(font);

	// draw second
	fontheight = 30;
	offsetX = offsetX + 152;
	offsetY = offsetY + 25;
	font = set_font(gc_time, path_ledFont, fontheight);
	strftime(secondbuf, sizeof(secondbuf), ":%S", tp);
	GrText(wid_root, gc_time, offsetX, offsetY, secondbuf, -1, GR_TFASCII|GR_TFTOP);
	GrDestroyFont(font);

	// draw lunar -----------
	/* fontheight = 15;
	offsetX = 120; // 20;
	offsetY = HEADER_HEIGHT + 5;
	font = set_font(gc_body, PATH_FONT_SYS, fontheight);
	char *lunar = cJSON_GetObjectItem(json_weather, "lunar")->valuestring;
	char lunarbuf[50];
	sprintf(lunarbuf, "%s %s", "农历", lunar);
	GrText(wid_root, gc_body, offsetX, offsetY, lunarbuf, strlen(lunarbuf), GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font); */
	
	//draw_weather
	if(json_weather != NULL)
	{
		draw_weather(json_weather);
	}
	if(json_weather_now != NULL)
	{
		draw_weatherNow(json_weather_now);
	}
}

// draw weather
void draw_weather(cJSON *root)
{
	//printf("\nstart draw_weather\n");
	
	// draw forecast array item ------
	cJSON *weatherArray = cJSON_GetObjectItem(root, "weatherArray");
	int offsetX = 5;
	int offsetY = 155;
	if(weatherArray)
	{
		int i = 0, size = 4; // size = 4;
		//size = cJSON_GetArraySize(weatherArray);
		
	    // the first
		draw_weatherItem(weatherArray, i, offsetX, HEADER_HEIGHT + 2);
		
		for(i = 1; i < size; i++)
		{
		   draw_weatherItem(weatherArray, i, GUIDE_BGWIDTH + 10 + (i-1) * 75, offsetY);
		}
	}
	
	// draw guide index ------
	cJSON *guideArray = cJSON_GetObjectItem(root, "guideArray");
	offsetX = 5; //CWIDTH / 2 + 30;
	offsetY = CHEIGHT / 2 + 35;
	if(guideArray)
	{
		int i = 0, size = 4;
		for(i = 0; i < size; i++)
		{
			draw_bgRect(offsetX - 2, offsetY  + i * ROWSPAN - 2, GUIDE_BGWIDTH, GUIDE_BGHEIGHT);
		   	draw_guideIndex(guideArray, i, offsetX, offsetY  + i * ROWSPAN);
		}
	}
}

// draw guide index
void draw_guideIndex(cJSON *guideArray, int index, int offsetX, int offsetY)
{
	//printf("\nstart draw_guideIndex\n");
	cJSON *arrayItem = cJSON_GetArrayItem(guideArray, index);
	if(!arrayItem) {
	   return;
	}
	
	char *title = cJSON_GetObjectItem(arrayItem,"title")->valuestring;
	char *guide = cJSON_GetObjectItem(arrayItem,"guide")->valuestring;
	//char *picUrl = cJSON_GetObjectItem(arrayItem,"picUrl")->valuestring;
		
	/* GR_IMAGE_INFO info = draw_image(offsetX+1, offsetY+1, picUrl);
	int image_width = info.width;
	int image_height = info.height; */
	
	char strbuf[50];
	sprintf(strbuf, "%s:%s", title, guide);
	int fontheight = 14;
	GR_FONT_ID font = set_font(gc_guide, PATH_FONT_SYS, fontheight);
	GrText(wid_root, gc_guide, offsetX, offsetY, strbuf, strlen(strbuf), GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font);
}

// draw bgRect
void draw_bgRect(int offsetX, int offsetY, int width, int height)
{
	//printf("\nstart draw_bgRect\n");
	GrFillRect(wid_root, gc_guide_bg, offsetX, offsetY, width, height);
}

// draw current weather
void draw_weatherNow(cJSON *weatherNowInfo)
{
	//printf("\nstart draw_weatherNow\n");
	char *temp = cJSON_GetObjectItem(weatherNowInfo,"temp")->valuestring;
	char *wind = cJSON_GetObjectItem(weatherNowInfo,"wind")->valuestring;
	char *humidity = cJSON_GetObjectItem(weatherNowInfo,"humidity")->valuestring;
	char *pm25 = cJSON_GetObjectItem(weatherNowInfo,"pm25")->valuestring;
	
	int offsetX = 5 + 50 + 15; //image width = 50
	int offsetY = HEADER_HEIGHT + 5;
	GR_FONT_ID font;
	int fontheight = 14;
	
	// temp
	char tempbuf[50];
	sprintf(tempbuf, "%s", temp);
	int str_len = strlen(tempbuf);
	fontheight = 25;
	font = set_font(gc_body, PATH_FONT_SYS, fontheight);
	GrText(wid_root, gc_body, offsetX, offsetY, tempbuf, str_len, GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font); //destroy font-----
	
	fontheight = 12;
	font = set_font(gc_body, PATH_FONT_SYS, fontheight);
	GrText(wid_root, gc_body, offsetX + str_len * 15, offsetY - 3, "o", 1, GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font); //destroy font-----
	
	// humidity
	char humbuf[50];
	sprintf(humbuf, "湿%s", humidity);
	fontheight = 14;
	font = set_font(gc_body, PATH_FONT_SYS, fontheight);
	GrText(wid_root, gc_body, offsetX, offsetY + ROWSPAN + 5, humbuf, strlen(humbuf), GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font); //destroy font-----
	
	//-----
	offsetX = 5;
	offsetY = CHEIGHT / 2 + 16;	
	// wind
	font = set_font(gc_body, PATH_FONT_SYS, fontheight);
	GrText(wid_root, gc_body, offsetX, offsetY - ROWSPAN, wind, strlen(wind), GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font); //destroy font-----
	
	// PM25-----
	int pm25_fgcolor = cJSON_GetObjectItem(weatherNowInfo, "pm25_fgcolor")->valueint;
	int pm25_bgcolor = cJSON_GetObjectItem(weatherNowInfo, "pm25_bgcolor")->valueint;
	
	GrSetGCForeground(gc_pm25, pm25_fgcolor);
	GrSetGCBackground(gc_pm25, pm25_bgcolor);
	GrSetGCForeground(gc_pm25_bg, pm25_bgcolor);
	GrSetGCBackground(gc_pm25_bg, pm25_bgcolor);
	
	font = set_font(gc_pm25, PATH_FONT_SYS, fontheight);
	GrFillRect(wid_root, gc_pm25_bg, offsetX - 2, offsetY - 5, GUIDE_BGWIDTH, GUIDE_BGHEIGHT);
	
	font = set_font(gc_pm25, PATH_FONT_SYS, fontheight);
	GrText(wid_root, gc_pm25, offsetX, offsetY - 2, pm25, strlen(pm25), GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font); //destroy font-----
}

// draw weather item
void draw_weatherItem(cJSON *weatherArray, int index, int offsetX, int offsetY)
{
	//printf("\nstart draw_weatherItem\n");
	cJSON *arrayItem = cJSON_GetArrayItem(weatherArray, index);
	if(!arrayItem) {
	   return;
	}
	
	char *week = cJSON_GetObjectItem(arrayItem,"week")->valuestring;
	char *low = cJSON_GetObjectItem(arrayItem,"low")->valuestring;
	char *high = cJSON_GetObjectItem(arrayItem,"high")->valuestring;
	char *weather = cJSON_GetObjectItem(arrayItem,"weather")->valuestring;
	//char *wind = cJSON_GetObjectItem(arrayItem,"wind")->valuestring;
	char *dayPicUrl = cJSON_GetObjectItem(arrayItem,"dayPicUrl")->valuestring;
	
	/* printf("week:%s\n", week);
	printf("low:%s\n", low);
	printf("high:%s\n", high);
	printf("weather:%s\n", weather);
	//printf("wind:%s\n", wind);
	printf("dayPicUrl:%s\n", dayPicUrl); */
		       
	// draw image
	GR_IMAGE_INFO info = draw_image(offsetX, offsetY, dayPicUrl);
	int image_width = info.width;
	int image_height = info.height;
		
	// image center offset
	int fontheight = 15;
	GR_FONT_ID font = set_font(gc_body, PATH_FONT_SYS, fontheight);
	int imageCenter_X = offsetX + image_width / 2;
	//int imageCenter_Y = offsetY + image_height / 2;

	offsetY -= 20;
	char tempbuf[10];
	sprintf(tempbuf, "%s/%s", low, high); //%s~%s° ℃
	int str_len = strlen(tempbuf);
	//printf("tempbuf:%s, strlen:%i\n", tempbuf, str_len);
	
	// draw week
	if(index > 0)
	{
		GrText(wid_root, gc_body, imageCenter_X - 12, offsetY, week, strlen(week), GR_TFUTF8|GR_TFTOP);
	}
	
	// draw temp
	double offsetTemp_X = imageCenter_X - str_len * 10/2;
	double offsetTemp_Y = offsetY + image_height + ROWSPAN-2;
	GrText(wid_root, gc_body, offsetTemp_X, offsetTemp_Y, tempbuf, str_len, GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font);

	// draw the unit
	fontheight = 12;
	font = set_font(gc_body, PATH_FONT_SYS, fontheight);
	GrText(wid_root, gc_body, offsetTemp_X + str_len * 10 - 5, offsetTemp_Y - 3, "o", 1, GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font);
	
	// draw weather
	fontheight = 14;
	font = set_font(gc_body, PATH_FONT_SYS, fontheight);
	str_len = strlen(weather);
	double offsetWeather_X = imageCenter_X - str_len * 4.6/2;
	double offsetWeather_Y = offsetTemp_Y + ROWSPAN-4;
	GrText(wid_root, gc_body, offsetWeather_X, offsetWeather_Y, weather, str_len, GR_TFUTF8|GR_TFTOP);
	GrDestroyFont(font);
}


GR_IMAGE_INFO draw_image(int offsetX, int offsetY, char* imageUrl)
{
	//printf("\nstart draw_image\n");
	GR_IMAGE_ID		image_id;
	GR_IMAGE_INFO	info;
	
	//printf("imageUrl: %s\n", imageUrl);
	strcpy(path_image, path_proc);
	strcat(path_image, imageUrl);
	//printf("path_image: %s\n", path_image);

	// draw image
	//GrDrawImageFromFile (wid_root, gc_body, offsetX, offsetY, image_width, image_height, path_image, FALSE);
	if (!(image_id = GrLoadImageFromFile(path_image, 0))) {
		fprintf(stderr, "Can't load image file: %s\n", path_image);
	}
	else 
	{
		GrGetImageInfo(image_id, &info);
		double img_scale = 1;
		int image_width = info.width * img_scale;
		int image_height = info.height * img_scale;
		//printf("image_width: %i, image_height:%i\n", image_width, image_height);
		GrDrawImageToFit(wid_root, gc_body, offsetX, offsetY, image_width, image_height, image_id);
		GrFreeImage(image_id);
	}
	
	return info;
}

GR_FONT_ID set_font(GR_GC_ID gc_body, GR_CHAR * fontname, int size)
{
	GR_FONT_ID	font;
	//GR_FONT_INFO	finfo;
	font = GrCreateFontEx(fontname, size, size, NULL);
	GrSetFontAttr(font, GR_TFANTIALIAS|GR_TFKERNING, -1);
	GrSetGCFont(gc_body, font);
	return font;
}

int Ymd2Wday(int year, int month, int days) 
{ 
  static int mdays[] = {0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30 }; 
  int i, y = year - 1; 
  for (i=0; i<month; ++i) days += mdays[i]; 
     if (month>2) { // Increment date if this is a leap year after February 
     if (((year%400)==0) || ((year&3)==0 && (year%100))) ++days; 
  } 
  return (y+y/4-y/100+y/400+days)%7; 
}

cJSON * read_weather()
{
	//printf("\nstart read_weather\n");
	
	// read from file
	FILE *fp;
	char str[FILE_LEN];
	
	if((fp=fopen(path_weather_file,"rt"))==NULL)
	{
		printf("Cannot open file strike any key exit!\n");
		return NULL;
	}
	fgets(str,FILE_LEN,fp);
	//printf("%s\n",str);
	fclose(fp);

	// json parse
	cJSON *root = cJSON_Parse(str);
	return root;
}

cJSON * read_weather_now()
{
	//printf("\nstart read_weather_now\n");
	
	// read from file
	FILE *fp;
	char str[FILE_LEN];
	
	if((fp=fopen(path_weatherNow_file,"rt"))==NULL)
	{
		printf("Cannot open file strike any key exit!\n");
		return NULL;
	}
	fgets(str,FILE_LEN,fp);
	//printf("%s\n",str);
	fclose(fp);

	// json parse
	cJSON *root = cJSON_Parse(str);
	return root;
}

