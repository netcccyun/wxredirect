<?php
namespace app\controller;

use app\BaseController;
use app\lib\WechatServer;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;
use think\facade\Cache;

class Admin extends BaseController
{
    public function verifycode()
    {
        return captcha();
    }

    public function login()
    {
        if (request()->islogin) {
            return redirect('/admin');
        }
        if (request()->isAjax()) {
            $username = input('post.username', null, 'trim');
            $password = input('post.password', null, 'trim');
            $code = input('post.captcha', null, 'trim');

            if (empty($username) || empty($password)) {
                return json(['code' => -1, 'msg' => '用户名或密码不能为空']);
            }
            if (!captcha_check($code)) {
                return json(['code' => -1, 'msg' => '验证码错误']);
            }
            if ($username === config_get('admin_username') && $password === config_get('admin_password')) {
                Db::name('log')->insert(['uid' => 0, 'action' => '登录后台', 'data' => 'IP:'.$this->clientip, 'addtime' => date("Y-m-d H:i:s")]);
                $session = md5($username.config_get('admin_password'));
                $expiretime = time() + 2562000;
                $token = authcode("{$username}\t{$session}\t{$expiretime}", 'ENCODE', config_get('syskey'));
                cookie('admin_token', $token, ['expire' => $expiretime, 'httponly' => true]);
                config_set('admin_lastlogin', date('Y-m-d H:i:s'));
                return json(['code' => 0]);
            } else {
                return json(['code' => -1, 'msg' => '用户名或密码错误']);
            }
        }
        return view();
    }

    public function logout()
    {
        cookie('admin_token', null);
        return redirect('/admin/login');
    }

    public function index()
    {
        if (request()->isAjax()) {
            if (input('post.do') == 'stat') {
                $stat = ['domains' => 0, 'logins' => 0, 'logins_today' => 0, 'tokens' => 0];
                $stat['domains'] = Db::name('domain')->where('status', 1)->count();
                $stat['logins'] = Db::name('record')->where('status', 1)->count();
                $stat['logins_today'] = Db::name('record')->whereTime('addtime', '>=', date("Y-m-d"))->where('status', 1)->count();
                $stat['tokens'] = Db::name('token')->where('status', 1)->count();
                return json($stat);
            } elseif (input('post.do') == 'cleanlogin') {
                Db::name('record')->whereTime('addtime', '<', date("Y-m-d H:i:s", strtotime("-24 hours")))->delete();
                Db::execute("OPTIMIZE TABLE `".config('database.connections.mysql.prefix')."record`");
                return json(['code' => 0]);
            }
            return json(['code' => -3]);
        }

        if (config('app.dbversion') && config_get('version') != config('app.dbversion')) {
            $this->db_update();
            config_set('version', config('app.dbversion'));
            Cache::clear();
        }

        $tmp = 'version()';
        $mysqlVersion = Db::query("select version()")[0][$tmp];
        $info = [
            'framework_version' => app()::VERSION,
            'php_version' => PHP_VERSION,
            'mysql_version' => $mysqlVersion,
            'software' => $_SERVER['SERVER_SOFTWARE'],
            'os' => php_uname(),
            'date' => date("Y-m-d H:i:s"),
        ];
        View::assign('info', $info);
        View::assign('checkupdate', '//auth.cccyun.cc/app/wechat.php?ver='.config('app.version'));
        return view();
    }

    private function db_update()
    {
        $sqls = file_get_contents(app()->getAppPath().'sql/update.sql');
        $mysql_prefix = env('database.prefix', 'wechat_');
        $sqls = explode(';', $sqls);
        foreach ($sqls as $value) {
            $value = trim($value);
            if (empty($value)) continue;
            $value = str_replace('wechat_', $mysql_prefix, $value);
            Db::execute($value);
        }
    }

    public function domain()
    {
        if (request()->isAjax()) {
            $act = input('post.act');
            if ($act == 'add') {
                $domain = input('post.domain', null, 'trim');
                if (empty($domain)) return json(['code' => -1, 'msg' => '域名不能为空']);
                if (!checkDomain($domain)) return json(['code' => -1, 'msg' => '域名格式不正确']);
                if (Db::name('domain')->where('domain', $domain)->find()) {
                    return json(['code' => -1, 'msg' => '该域名已存在，请勿重复添加']);
                }
                Db::name('domain')->insert([
                    'domain' => $domain,
                    'status' => 1,
                    'addtime' => date("Y-m-d H:i:s")
                ]);
                return json(['code' => 0, 'msg' => '添加域名成功！']);
            } elseif ($act == 'set') {
                $id = input('post.id/d');
                $status = input('post.status/d');
                Db::name('domain')->where('id', $id)->update(['status' => $status]);
                return json(['code' => 0]);
            } elseif ($act == 'del') {
                $id = input('post.id/d');
                Db::name('domain')->where('id', $id)->delete();
                return json(['code' => 0]);
            }
            return json(['code' => -3]);
        }
        return view();
    }

    public function domain_data()
    {
        $kw = input('post.kw', null, 'trim');
        $offset = input('post.offset/d');
        $limit = input('post.limit/d');

        $select = Db::name('domain');
        if (!empty($kw)) {
            $select->whereLike('domain', '%'.$kw.'%');
        }
        $total = $select->count();
        $rows = $select->order('id', 'desc')->limit($offset, $limit)->select();

        return json(['total' => $total, 'rows' => $rows]);
    }

    public function record()
    {
        return view();
    }

    public function record_data()
    {
        $domain = input('post.domain', null, 'trim');
        $did = input('post.did/d');
        $appid = input('post.appid', null, 'trim');
        $offset = input('post.offset/d');
        $limit = input('post.limit/d');

        $select = Db::name('record')->alias('A')->join('domain B', 'A.did = B.id')->where('A.status', 1);
        if (!empty($domain)) {
            $did = Db::name('domain')->where('domain', $domain)->value('id');
            $select->where('did', $did);
        } elseif (!empty($did)) {
            $select->where('did', $did);
        }
        if (!empty($appid)) {
            $select->where('appid', $appid);
        }
        $total = $select->count();
        $rows = $select->order('A.id', 'desc')->limit($offset, $limit)->field('A.*,B.domain')->select();

        return json(['total' => $total, 'rows' => $rows]);
    }

    public function wxtoken()
    {
        if (request()->isAjax()) {
            $act = input('get.act');
            if ($act == 'get') {
                $id = input('get.id/d');
                $row = Db::name('token')->where('id', $id)->find();
                return json(['code' => 0, 'data' => $row]);
            } elseif ($act == 'set') {
                $id = input('get.id/d');
                $status = input('get.status/d');
                Db::name('token')->where('id', $id)->update(['status' => $status]);
                return json(['code' => 0]);
            } elseif ($act == 'del') {
                $id = input('get.id/d');
                Db::name('token')->where('id', $id)->delete();
                return json(['code' => 0]);
            } elseif ($act == 'save') {
                $id = input('post.id/d');
                $type = input('post.type/d');
                $name = input('post.name', null, 'trim');
                $appid = input('post.appid', null, 'trim');
                $appsecret = input('post.appsecret', null, 'trim');
                if (!$name || !$appid || !$appsecret) return json(['code' => -1, 'msg' => '必填项不能为空']);
                if (input('post.action') == 'add') {
                    if (Db::name('token')->where('name', $name)->find()) {
                        return json(['code' => -1, 'msg' => '名称重复']);
                    }
                    Db::name('token')->insert([
                        'type' => $type,
                        'name' => $name,
                        'status' => 1,
                        'appid' => $appid,
                        'appsecret' => $appsecret,
                        'addtime' => date("Y-m-d H:i:s")
                    ]);
                    return json(['code' => 0, 'msg' => '添加微信Token成功！']);
                } else {
                    if (Db::name('token')->where('name', $name)->where('id', '<>', $id)->find()) {
                        return json(['code' => -1, 'msg' => '名称重复']);
                    }
                    Db::name('token')->where('id', $id)->update([
                        'type' => $type,
                        'name' => $name,
                        'appid' => $appid,
                        'appsecret' => $appsecret,
                    ]);
                    return json(['code' => 0, 'msg' => '修改微信Token成功！']);
                }
            }
            return json(['code' => -3]);
        }
        return view();
    }

    public function wxtoken_data()
    {
        $kw = input('post.kw', null, 'trim');
        $offset = input('post.offset/d');
        $limit = input('post.limit/d');

        $select = Db::name('token');
        if (!empty($kw)) {
            $select->whereLike('name|appid', '%'.$kw.'%');
        }
        $total = $select->count();
        $rows = $select->order('id', 'desc')->limit($offset, $limit)->select();

        return json(['total' => $total, 'rows' => $rows]);
    }

    public function textwxtoken()
    {
        $id = input('post.id/d');
        $row = Db::name('token')->where('id', $id)->find();
        if (!$row) return json(['code' => -1, 'msg' => '记录不存在']);
        try {
            if ($row['type'] == 3) {
                refresh_qywx_access_token($row['id'], true);
            } else {
                refresh_wx_access_token($row['id'], true);
            }
            return json(['code' => 0, 'msg' => '接口连接测试成功！']);
        } catch (\Exception $e) {
            return json(['code' => -1, 'msg' => $e->getMessage()]);
        }
    }

    public function servergroup()
    {
        if (request()->isAjax()) {
            $act = input('get.act');
            if ($act == 'get') {
                $id = input('get.id/d');
                $row = Db::name('servergroup')->where('id', $id)->find();
                $row['url'] = request()->root(true).'/wxserver/id/'.$id;
                return json(['code' => 0, 'data' => $row]);
            } elseif ($act == 'del') {
                $id = input('get.id/d');
                Db::name('servergroup')->where('id', $id)->delete();
                Db::name('serveritem')->where('gid', $id)->delete();
                return json(['code' => 0]);
            } elseif ($act == 'save') {
                $id = input('post.id/d');
                $type = input('post.type/d');
                $mode = input('post.mode/d');
                $name = input('post.name', null, 'trim');
                $server_token = input('post.server_token', null, 'trim');
                $server_enckey = input('post.server_enckey', null, 'trim');
                if (!$name) return json(['code' => -1, 'msg' => '名称不能为空']);
                if (input('post.action') == 'add') {
                    if (Db::name('servergroup')->where('name', $name)->find()) {
                        return json(['code' => -1, 'msg' => '名称重复']);
                    }
                    Db::name('servergroup')->insert([
                        'type' => $type,
                        'mode' => $mode,
                        'name' => $name,
                        'token' => md5(uniqid(mt_rand(), true).microtime()),
                        'addtime' => date("Y-m-d H:i:s")
                    ]);
                    return json(['code' => 0, 'msg' => '添加微信转发服务器组成功！其他信息请点击修改信息查看']);
                } else {
                    if (!$server_token) return json(['code' => -1, 'msg' => 'Token不能为空']);
                    if (Db::name('servergroup')->where('name', $name)->where('id', '<>', $id)->find()) {
                        return json(['code' => -1, 'msg' => '名称重复']);
                    }
                    Db::name('servergroup')->where('id', $id)->update([
                        'type' => $type,
                        'mode' => $mode,
                        'name' => $name,
                        'token' => $server_token,
                        'enckey' => $type == 1 ? $server_enckey : null,
                    ]);
                    return json(['code' => 0, 'msg' => '修改微信转发服务器组成功！']);
                }
            }
            return json(['code' => -3]);
        }
        return view();
    }

    public function servergroup_data()
    {
        $kw = input('post.kw', null, 'trim');
        $offset = input('post.offset/d');
        $limit = input('post.limit/d');

        $select = Db::name('servergroup');
        if (!empty($kw)) {
            $select->whereLike('name', '%'.$kw.'%');
        }
        $total = $select->count();
        $rows = $select->alias('A')->fieldRaw("A.*,(SELECT COUNT(*) FROM ".env('database.prefix', 'wechat_')."serveritem WHERE gid=A.id) itemnum")->order('id', 'desc')->limit($offset, $limit)->select();

        return json(['total' => $total, 'rows' => $rows]);
    }

    public function serveritem()
    {
        if (request()->isAjax()) {
            $act = input('get.act');
            if ($act == 'get') {
                $id = input('get.id/d');
                $row = Db::name('serveritem')->where('id', $id)->find();
                return json(['code' => 0, 'data' => $row]);
            } elseif ($act == 'del') {
                $id = input('get.id/d');
                Db::name('serveritem')->where('id', $id)->delete();
                return json(['code' => 0]);
            } elseif ($act == 'set') {
                $id = input('post.id/d');
                $status = input('post.status/d');
                Db::name('serveritem')->where('id', $id)->update(['status' => $status]);
                return json(['code' => 0]);
            } elseif ($act == 'save') {
                $id = input('post.id/d');
                $gid = input('post.gid/d');
                $sort = input('post.sort/d');
                $server_url = input('post.server_url', null, 'trim');
                if (!$server_url) return json(['code' => -1, 'msg' => '服务器URL不能为空']);
                if (parse_url($server_url)['host'] == request()->host()) return json(['code' => -1, 'msg' => '服务器URL不能是当前站点']);
                if (input('post.action') == 'add') {
                    if (Db::name('serveritem')->where('gid', $gid)->where('url', $server_url)->find()) {
                        return json(['code' => -1, 'msg' => '服务器URL已存在']);
                    }
                    $group = Db::name('servergroup')->where('id', $gid)->find();
                    $server = new WechatServer($group['token'], $group['enckey']);
                    if ($group['type'] == 0 && !$server->verifyWechatServer($server_url) || $group['type'] == 1 && !$server->verifyWeWorkServer($server_url)) {
                        return json(['code' => -1, 'msg' => '服务器Token验证失败']);
                    }
                    Db::name('serveritem')->insert([
                        'gid' => $gid,
                        'sort' => $sort,
                        'url' => $server_url,
                        'status' => 1,
                        'addtime' => date("Y-m-d H:i:s")
                    ]);
                    return json(['code' => 0, 'msg' => '添加转发服务器成功！']);
                } else {
                    if (Db::name('serveritem')->where('gid', $gid)->where('url', $server_url)->where('id', '<>', $id)->find()) {
                        return json(['code' => -1, 'msg' => '服务器URL已存在']);
                    }
                    Db::name('serveritem')->where('id', $id)->update([
                        'sort' => $sort,
                        'url' => $server_url,
                    ]);
                    return json(['code' => 0, 'msg' => '修改转发服务器成功！']);
                }
            }
            return json(['code' => -3]);
        }
        $gid = input('get.gid/d');
        $rows = Db::name('serveritem')->where('gid', $gid)->order('sort', 'asc')->select();
        View::assign('gid', $gid);
        View::assign('rows', $rows);
        return view();
    }

    public function set()
    {
        if (request()->isAjax()) {
            $params = Request::param();
            if (isset($params['username'])) $params['username'] = trim($params['username']);
            if (isset($params['oldpwd'])) $params['oldpwd'] = trim($params['oldpwd']);
            if (isset($params['newpwd'])) $params['newpwd'] = trim($params['newpwd']);
            if (isset($params['newpwd2'])) $params['newpwd2'] = trim($params['newpwd2']);

            if (empty($params['username'])) return json(['code' => -1, 'msg' => '用户名不能为空']);

            config_set('admin_username', $params['username']);

            if (!empty($params['oldpwd']) && !empty($params['newpwd']) && !empty($params['newpwd2'])) {
                if (config_get('admin_password') != $params['oldpwd']) {
                    return json(['code' => -1, 'msg' => '旧密码不正确']);
                }
                if ($params['newpwd'] != $params['newpwd2']) {
                    return json(['code' => -1, 'msg' => '两次新密码输入不一致']);
                }
                config_set('admin_password', $params['newpwd']);
            }
            cache('configs', NULL);
            cookie('admin_token', null);
            return json(['code' => 0]);
        }
        $mod = input('param.mod', 'sys');
        View::assign('mod', $mod);
        View::assign('conf', config('sys'));
        return view();
    }

    public function cleancache()
    {
        Cache::clear();
        return json(['code' => 0, 'msg' => 'succ']);
    }

    public function doc()
    {
        View::assign('siteurl', request()->root(true));
        return view();
    }
}
