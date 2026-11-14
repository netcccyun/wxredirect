<?php
namespace app;

// 应用请求对象类
class Request extends \think\Request
{
    /** @var bool 后台是否已登录 */
    public $islogin = false;

    /**
     * 当前是否Ajax请求
     * @access public
     * @param  bool $ajax 兼容父类参数
     * @return bool
     */
    public function isAjax(bool $ajax = false): bool
    {
        $value = $this->server('HTTP_X_REQUESTED_WITH');
        return $value && 'xmlhttprequest' == strtolower($value);
    }
}
