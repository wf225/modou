#include <stdio.h>
#include "nano-X.h"
#include "nxcolors.h"

int main()
{
    GR_WINDOW_ID root_wid, wid;
    GR_GC_ID gc;
    GR_COORD x, y;
    GR_SIZE width, height;
    GR_EVENT event;
    GR_FONT_ID fid;

    x = 0;
    y = 0;
    width = 340;
    height = 240;

    if (GrOpen() < 0)
    {
        printf("Can't open graphics/n");
        return 0;
    }

    gc = GrNewGC();
    fid = GrCreateFont("HZKFONT", 16, NULL);
    // 创建父窗口（根窗口）
    root_wid = GrNewWindow(GR_ROOT_WINDOW_ID, x, y, width, height,
            1, GR_COLOR_ROYALBLUE, GR_COLOR_BLACK);
    // 创建一个子窗口
    wid=GrNewWindow(root_wid,60,60,200,60,1,GR_COLOR_BLACK,GR_COLOR_WHITE);
    GrMapWindow(root_wid);  // 绘制父窗口
    GrMapWindow(wid);       // 绘制子窗口

    // 显示在父窗口中的文字
    GrSetGCForeground(gc, GR_COLOR_RED);   // 前景色（字体颜色）
    GrSetGCBackground(gc, GR_COLOR_GREEN); // 背景色（字体背景颜色）
    GrSetGCFont(gc, fid);
    GrText(root_wid, gc, 10, 20, "你好 in root_wid", -1, GR_TFBOTTOM);

    // 显示在子窗口中的文字
    GrSetGCForeground(gc, GR_COLOR_RED);   // 前景色（字体颜色）
    GrSetGCBackground(gc, GR_COLOR_GREEN); // 背景色（字体背景颜色）
    GrText(wid, gc, 10, 20, "你好 in wid", -1, GR_TFBOTTOM);

    for (;;)
    {
        GrGetNextEvent(&event);
    }
    GrClose();

    return 1;
}