<?php
namespace app\controller;

use app\BaseController;
use app\lib\WechatServer;
use Exception;
use think\facade\Db;
use think\facade\View;

class Index extends BaseController
{
    public function index()
    {
        return view();
    }

    //微信公众平台跳转
    public function connect(){
        $appid = input('get.appid');
        $redirect_uri = input('get.redirect_uri');
        $scope = input('?get.scope') ? input('get.scope') : 'snsapi_base';
        $state = input('get.state');
        if(!$appid || !$redirect_uri || !$scope) return $this->error('参数不能为空');
        if(!filter_var($redirect_uri, FILTER_VALIDATE_URL)) return $this->error('redirect_uri参数错误');

        $drow = Db::name('domain')->whereRaw('(domain=:domain1 OR domain=:domain2) AND status=1', ['domain1'=>get_host($redirect_uri), 'domain2'=>'*.'.get_main_host($redirect_uri)])->find();
        if(!$drow) return $this->error('回调域名未授权');

        $id = Db::name('record')->insertGetId([
            'did' => $drow['id'],
            'type' => 0,
            'status' => 1,
            'appid' => $appid,
            'redirect_uri' => $redirect_uri,
            'state' => $state,
            'ip' => $this->clientip,
            'addtime' => date("Y-m-d H:i:s"),
            'status' => 0
        ]);

        $state = authcode2($id, 'ENCODE', config_get('syskey'));
        $redirect_uri = request()->root(true) . '/return';

        $apiurl = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $param = [
            "appid" => $appid,
            "redirect_uri" => $redirect_uri,
            "response_type" => "code",
            "scope" => $scope,
            "state" => $state
        ];
        if(input('?get.agentid')) $param['agentid'] = input('get.agentid');
        $url = $apiurl.'?'.http_build_query($param).'#wechat_redirect';
        return redirect($url);
    }

    //微信开放平台跳转
    public function qrconnect(){
        $appid = input('get.appid');
        $redirect_uri = input('get.redirect_uri');
        $scope = input('?get.scope') ? input('get.scope') : 'snsapi_login';
        $state = input('get.state');
        if(!$appid || !$redirect_uri || !$scope) return $this->error('参数不能为空');
        if(!filter_var($redirect_uri, FILTER_VALIDATE_URL)) return $this->error('redirect_uri参数错误');

        $drow = Db::name('domain')->whereRaw('(domain=:domain1 OR domain=:domain2) AND status=1', ['domain1'=>get_host($redirect_uri), 'domain2'=>'*.'.get_main_host($redirect_uri)])->find();
        if(!$drow) return $this->error('回调域名未授权');

        $id = Db::name('record')->insertGetId([
            'did' => $drow['id'],
            'type' => 1,
            'status' => 1,
            'appid' => $appid,
            'redirect_uri' => $redirect_uri,
            'state' => $state,
            'ip' => $this->clientip,
            'addtime' => date("Y-m-d H:i:s"),
            'status' => 0
        ]);

        $state = authcode2($id, 'ENCODE', config_get('syskey'));
        $redirect_uri = request()->root(true) . '/return';

        $apiurl = 'https://open.weixin.qq.com/connect/qrconnect';
        $param = [
            "appid" => $appid,
            "redirect_uri" => $redirect_uri,
            "response_type" => "code",
            "scope" => $scope,
            "state" => $state
        ];
        $url = $apiurl.'?'.http_build_query($param).'#wechat_redirect';
        return redirect($url);
    }

    //支付宝登录跳转
    public function alipayoauth(){
        $appid = input('get.app_id');
        $redirect_uri = input('get.redirect_uri');
        $scope = input('get.scope');
        $state = input('get.state');
        if(!$appid || !$redirect_uri || !$scope) return $this->error('参数不能为空');
        if(!filter_var($redirect_uri, FILTER_VALIDATE_URL)) return $this->error('redirect_uri参数错误');

        $drow = Db::name('domain')->whereRaw('(domain=:domain1 OR domain=:domain2) AND status=1', ['domain1'=>get_host($redirect_uri), 'domain2'=>'*.'.get_main_host($redirect_uri)])->find();
        if(!$drow) return $this->error('回调域名未授权');

        $id = Db::name('record')->insertGetId([
            'did' => $drow['id'],
            'type' => 2,
            'status' => 1,
            'appid' => $appid,
            'redirect_uri' => $redirect_uri,
            'state' => $state,
            'ip' => $this->clientip,
            'addtime' => date("Y-m-d H:i:s"),
            'status' => 0
        ]);

        $state = authcode2($id, 'ENCODE', config_get('syskey'));
        $redirect_uri = request()->root(true) . '/alipayreturn';

        $apiurl = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm';
        $param = [
            "app_id" => $appid,
            "scope" => $scope,
            "redirect_uri" => $redirect_uri,
            "state" => $state
        ];
        if(input('?get.cert_verify_id')) $param['cert_verify_id'] = input('get.cert_verify_id');
        $url = $apiurl.'?'.http_build_query($param);
        return redirect($url);
    }

    //跳转回调
    public function return(){
        $code = input('get.code');
        $state = input('get.state');
        if(!$code || !$state) return $this->error('回调参数不能为空');

        $id = authcode2($state, 'DECODE', config_get('syskey'));
        if(!$id) return $this->error('state参数错误');

        $row = Db::name('record')->where('id', $id)->find();
        if(!$row) return $this->error('记录不存在');
        if(strtotime($row['addtime'])<time()-60*5) return $this->error('已超时');
        Db::name('record')->where('id', $id)->update(['status'=>1, 'endtime'=>date("Y-m-d H:i:s")]);

        $redirect_uri = $row['redirect_uri'];
        if(strpos($redirect_uri, '?')!==false){
            $redirect_uri .= '&';
        }else{
            $redirect_uri .= '?';
        }
        $redirect_uri .= 'code='.urlencode($code);
        if(!empty($row['state'])){
            $redirect_uri .= '&state='.urlencode($row['state']);
        }
        return redirect($redirect_uri);
    }

    //支付宝跳转回调
    public function alipayreturn(){
        $code = input('get.auth_code');
        $state = input('get.state');
        if(!$code || !$state) return $this->error('回调参数不能为空');

        $id = authcode2($state, 'DECODE', config_get('syskey'));
        if(!$id) return $this->error('state参数错误');

        $row = Db::name('record')->where('id', $id)->find();
        if(!$row) return $this->error('记录不存在');
        if(strtotime($row['addtime'])<time()-60*5) return $this->error('已超时');
        Db::name('record')->where('id', $id)->update(['status'=>1, 'endtime'=>date("Y-m-d H:i:s")]);

        $redirect_uri = $row['redirect_uri'];
        if(strpos($redirect_uri, '?')!==false){
            $redirect_uri .= '&';
        }else{
            $redirect_uri .= '?';
        }
        $redirect_uri .= 'auth_code='.urlencode($code);
        if(!empty($row['state'])){
            $redirect_uri .= '&state='.urlencode($row['state']);
        }
        if(input('?get.cert_verify_id')) $redirect_uri .= '&cert_verify_id='.input('get.cert_verify_id');
        return redirect($redirect_uri);
    }

    //获取AccessToken
    public function token(){
        $appid = input('get.appid', null, 'trim');
        $appsecret = input('get.secret', null, 'trim');
        if(!$appid) return json(['errcode'=>40001, 'errmsg'=>'appid missing']);
        if(!$appsecret) return json(['errcode'=>40002, 'errmsg'=>'appsecret missing']);

        $row = Db::name('token')->where('appid', $appid)->find();
        if(!$row) return json(['errcode'=>40003, 'errmsg'=>'invalid appid']);
        if($row['appsecret'] !== $appsecret) return json(['errcode'=>40003, 'errmsg'=>'invalid appsecret']);

        if($row['access_token'] && strtotime($row['expiretime']) - 180 >= time()){
            $expires_in = strtotime($row['expiretime']) - time();
            return json(['access_token'=>$row['access_token'], 'expires_in'=>$expires_in]);
        }else{
            try{
                list($access_token, $expires_in) = refresh_wx_access_token($row['id']);
                return json(['access_token'=>$access_token, 'expires_in'=>$expires_in]);
            }catch(Exception $e){
                return json(['errcode'=>40004, 'errmsg'=>$e->getMessage()]);
            }
        }
    }

    //企业微信获取AccessToken
    public function qytoken(){
        $corpid = input('get.corpid', null, 'trim');
        $corpsecret = input('get.corpsecret', null, 'trim');
        if(!$corpid) return json(['errcode'=>40001, 'errmsg'=>'corpid missing']);
        if(!$corpsecret) return json(['errcode'=>40002, 'errmsg'=>'corpsecret missing']);

        $row = Db::name('token')->where('appid', $corpid)->where('type', 3)->find();
        if(!$row) return json(['errcode'=>40003, 'errmsg'=>'invalid corpid']);
        if($row['appsecret'] !== $corpsecret) return json(['errcode'=>40003, 'errmsg'=>'invalid corpsecret']);

        if($row['access_token'] && strtotime($row['expiretime']) - 180 >= time()){
            $expires_in = strtotime($row['expiretime']) - time();
            return json(['access_token'=>$row['access_token'], 'expires_in'=>$expires_in]);
        }else{
            try{
                list($access_token, $expires_in) = refresh_qywx_access_token($row['id']);
                return json(['access_token'=>$access_token, 'expires_in'=>$expires_in]);
            }catch(Exception $e){
                return json(['errcode'=>40004, 'errmsg'=>$e->getMessage()]);
            }
        }
    }

    //微信消息事件转发
    public function wxserver(){
        $id = input('param.id/d');
        if(!$id) exit('param error');
        $group = Db::name('servergroup')->where('id', $id)->find();
        if(!$group) exit('服务器组不存在');
        $items = Db::name('serveritem')->where('gid', $id)->where('status', 1)->order('sort','asc')->select();

        $resData = '';
        $server = new WechatServer($group['token'], $group['enckey']);
        $xml = $group['type'] == 1 ? $server->getWeWorkRequest() : $server->getWechatRequest();
        
        if(count($items) == 0) exit('组内服务器数量为0');

        foreach($items as $row){
            $res = $server->sendRequest($row['url'], $xml);
            if($res[0] === true){
                if(empty($resData)) $resData = $res[1];
                Db::name('serveritem')->where('id', $row['id'])->update(['enable' => 1]);
                if($group['mode'] == 1) break;
            }else{
                Db::name('serveritem')->where('id', $row['id'])->update(['enable' => 2]);
            }
        }
        Db::name('servergroup')->where('id', $id)->inc('count')->update(['usetime' => date("Y-m-d H:i:s")]);
        exit($resData);
    }

    private function error($msg){
        View::assign('errmsg', $msg);
        return view('error');
    }

}
