应用配置
http://sae.sina.com.cn/doc/php/runtime.html#php-app-config

应用可以通过应用版本目录下的 config.yaml 来对Apache服务器做一些配置（类似于Apache的htaccess文件）。

通过配置，开发者可以很方便的实现以下功能：

目录默认页面
自定义错误页面
压缩
页面重定向
页面过期
设置响应头的content-type
设置页面访问权限
注解
PHP Runtime的config.yaml文件不会部署到代码目录中，而只是存在于SVN中。

应用配置写在 config.yaml 文件的 handle 下，例如:

name: saetest
version: 1

handle:
- rewrite: if (!-d && !-f) goto "/index.php?%{QUERY_STRING}"