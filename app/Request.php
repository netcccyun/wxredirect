<?php
namespace app;

// 应用请求对象类
class Request extends \think\Request
{
    /** @var bool 后台是否已登录 */
    public $islogin = false;
}
