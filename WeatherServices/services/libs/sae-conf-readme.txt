Ӧ������
http://sae.sina.com.cn/doc/php/runtime.html#php-app-config

Ӧ�ÿ���ͨ��Ӧ�ð汾Ŀ¼�µ� config.yaml ����Apache��������һЩ���ã�������Apache��htaccess�ļ�����

ͨ�����ã������߿��Ժܷ����ʵ�����¹��ܣ�

Ŀ¼Ĭ��ҳ��
�Զ������ҳ��
ѹ��
ҳ���ض���
ҳ�����
������Ӧͷ��content-type
����ҳ�����Ȩ��
ע��
PHP Runtime��config.yaml�ļ����Ჿ�𵽴���Ŀ¼�У���ֻ�Ǵ�����SVN�С�

Ӧ������д�� config.yaml �ļ��� handle �£�����:

name: saetest
version: 1

handle:
- rewrite: if (!-d && !-f) goto "/index.php?%{QUERY_STRING}"