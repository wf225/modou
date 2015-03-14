#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <sys/time.h>

#define MWINCLUDECOLORS

#include "nano-X.h"



GR_WINDOW_ID wid;

GR_GC_ID gc;



void event_handler (GR_EVENT *event);
void draw_time(void);



int main (void)

{

	if (GrOpen() < 0)

	{

	fprintf (stderr, "GrOpen failed");

	exit (1);

	}



	gc = GrNewGC();



	GrSetGCUseBackground (gc, GR_FALSE);

	GrSetGCForeground (gc, RED);

	wid = GrNewWindowEx (GR_WM_PROPS_APPFRAME |

						GR_WM_PROPS_CAPTION |

						GR_WM_PROPS_CLOSEBOX,

						"Hello Window",

						GR_ROOT_WINDOW_ID,

						50, 50, 200, 100, WHITE);

	GrSelectEvents (wid, GR_EVENT_MASK_EXPOSURE |

					GR_EVENT_MASK_CLOSE_REQ);

	GrMapWindow (wid);

	GrMainLoop (event_handler);

}



void event_handler (GR_EVENT *event)

{
	while (1) 
	{
		GrGetNextEventTimeout(&event, 500L);


		switch (event->type)

		{

			case GR_EVENT_TYPE_EXPOSURE:

				//GrText (wid, gc, 50, 50, "Hello World", -1, GR_TFASCII);
				draw_time();

				break;

		

			case GR_EVENT_TYPE_CLOSE_REQ:

				GrClose();

				exit (0);

		}
	}

}

void draw_time()
{
	struct timeval tv;
	struct timezone tz;
	struct tm * tp;
	time_t now;
	char	buf[64];

	gettimeofday(&tv, &tz);
	now = tv.tv_sec - (60 * tz.tz_minuteswest);
	tp = gmtime(&now);

	sprintf(buf, "%d/%d/%d %d:%d:%d", (1900+tp->tm_year), (1+tp->tm_mon), tp->tm_mday, tp->tm_hour + 8, tp->tm_min, tp->tm_sec);

	//sprintf(buf, "%d/%d 2014-09-07 15:46:20", height, width);
	GrText(wid, gc, 50, 50, buf, -1, GR_TFASCII|GR_TFTOP);

}