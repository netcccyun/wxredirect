## 微信公众号多域名回调系统

这是一款基于ThinkPHP6.0开发的微信公众号多域名回调系统。

微信公众号后台默认只能授权2个网页域名，用本系统突破这个限制，用同一个公众号对接无限多个网站。网站后台支持回调域名白名单的管理，以及登录记录的查看。

本系统还有微信access_token的获取功能，可让当前站点作为中控服务器统一获取和刷新access_token，其他业务逻辑站点所使用的access_token均调用当前站点获取，这样可避免各自刷新造成冲突，导致access_token覆盖而影响业务。

### 部署方法

* 运行环境要求PHP7.4+，MySQL5.6+
* 设置网站运行目录为`public`
* 设置伪静态为`ThinkPHP`
* 访问网站，会自动跳转到安装页面，根据提示安装完成
* 访问 /admin 进入后台管理

##### 伪静态规则

* Nginx

```
location / {
	if (!-e $request_filename){
		rewrite  ^(.*)$  /index.php?s=$1  last;   break;
	}
}
```

* Apache

```
<IfModule mod_rewrite.c>
  Options +FollowSymlinks -Multiviews
  RewriteEngine On

  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
</IfModule>
```

### 版权信息

版权所有Copyright © 2022 by 消失的彩虹海(https://blog.cccyun.cn)

