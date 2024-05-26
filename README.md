## 微信公众号多域名回调系统

这是一款基于ThinkPHP6.0开发的微信公众号多域名回调系统。本系统有如下功能：

- 微信公众号多域名回调功能：微信公众号后台默认只能授权2个网页域名，用本系统突破这个限制，用同一个公众号对接无限多个网站。网站后台支持回调域名白名单的管理，以及登录记录的查看。
- ~~微信access_token获取功能~~（建议改用微信新出的稳定版token）。
- 微信消息事件转发功能：微信公众平台/企业微信的服务器设置只能填写1个url，用本系统可以同时转发给多个url。如用户关注事件、用户发送的消息等，可以同时转发给到多个站点。
- 支付宝开放平台多域名回调功能：每个支付宝开发平台应用只能配置1个授权回调域名，用本系统突破这个限制，可同时在多个网站使用同一个支付宝开放平台应用。

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

版权所有Copyright © 2022~2024 by 消失的彩虹海(https://blog.cccyun.cn)

