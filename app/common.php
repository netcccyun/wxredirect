<?php
// 应用公共文件
use think\facade\Db;

function get_curl($url, $post=0, $referer=0, $cookie=0, $header=0, $ua=0, $nobody=0, $addheader=0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$httpheader[] = "Accept: */*";
	$httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
	$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
	$httpheader[] = "Connection: close";
	if($addheader){
		$httpheader = array_merge($httpheader, $addheader);
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	if ($header) {
		curl_setopt($ch, CURLOPT_HEADER, true);
	}
	if ($cookie) {
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	if($referer){
		curl_setopt($ch, CURLOPT_REFERER, $referer);
	}
	if ($ua) {
		curl_setopt($ch, CURLOPT_USERAGENT, $ua);
	}
	else {
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.0.4; es-mx; HTC_One_X Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0");
	}
	if ($nobody) {
		curl_setopt($ch, CURLOPT_NOBODY, 1);
	}
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$ret = curl_exec($ch);
	curl_close($ch);
	return $ret;
}

function real_ip($type=0){
    $ip = $_SERVER['REMOTE_ADDR'];
    if($type<=0 && isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
        foreach ($matches[0] AS $xip) {
            if (filter_var($xip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip = $xip;
                break;
            }
        }
    } elseif ($type<=0 && isset($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ($type<=1 && isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif ($type<=1 && isset($_SERVER['HTTP_X_REAL_IP']) && filter_var($_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    return $ip;
}

function strexists($string, $find) {
	return !(strpos($string, $find) === FALSE);
}

function dstrpos($string, $arr) {
	if(empty($string)) return false;
	foreach((array)$arr as $v) {
		if(strpos($string, $v) !== false) {
			return true;
		}
	}
	return false;
}

function checkmobile() {
	$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
	$ualist = array('android', 'midp', 'nokia', 'mobile', 'iphone', 'ipod', 'blackberry', 'windows phone');
	if((dstrpos($useragent, $ualist) || strexists($_SERVER['HTTP_ACCEPT'], "VND.WAP") || strexists($_SERVER['HTTP_VIA'],"wap")))
		return true;
	else
		return false;
}

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);
	$result = '';
	$box = range(0, 255);
	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	if($operation == 'DECODE') {
		if(((int)substr($result, 0, 10) == 0 || (int)substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.base64_encode($result);
	}
}
function base62_encode($data){
    $base62str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $data = strval($data);
    $base62 = str_split($base62str);
    $len = strlen($data);
    $i = 0;
    $tmpArr = array();
    while($i<$len){
        $val = $data[$i];
        $tmp = str_pad(decbin(ord($data[$i])),8,'0',STR_PAD_LEFT );
        $temp = str_split($tmp,4);
        $tmpArr = array_merge($tmpArr,$temp);
        ++$i;
    }
    $result = '';
    $i = 0;
    foreach($tmpArr as $arr){
        $temp = bindec($arr)*4+$i%2;
        $result .= $base62[$temp];
        ++$i;
    }
    return $result;
}
function base62_decode($data){
    $base62str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $data = strval($data);
    $base62 = str_split($base62str);
    $base62Arr = array_flip($base62);
    if(!preg_match('/[a-zA-Z0-9]+/',$data)){
        return false;
    }
    $len = strlen($data);
    $i = 0;
    $tempArr = array();
    while($i<$len){
        $temp = decbin(($base62Arr[$data[$i]]-$i%2)/4);
        $tempArr[] = str_pad($temp,4,'0',STR_PAD_LEFT);
        ++$i;
    }
    $result = '';
    $tempArr = array_chunk($tempArr,2);
    foreach($tempArr as $arr){
        $result .= chr(bindec(join('',$arr)));
    }
    return $result;
}
function authcode2($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);
	$string = $operation == 'DECODE' ? base62_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);
	$result = '';
	$box = range(0, 255);
	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	if($operation == 'DECODE') {
		if(((int)substr($result, 0, 10) == 0 || (int)substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.base62_encode($result);
	}
}

function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	$hash = '';
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}

function sysmsg($msg = '未知的异常',$title = '站点提示信息') {
    ?>  
    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title?></title>
        <style type="text/css">
html{background:#eee}body{background:#fff;color:#333;font-family:"微软雅黑","Microsoft YaHei",sans-serif;margin:2em auto;padding:1em 2em;max-width:700px;-webkit-box-shadow:10px 10px 10px rgba(0,0,0,.13);box-shadow:10px 10px 10px rgba(0,0,0,.13);opacity:.8}h1{border-bottom:1px solid #dadada;clear:both;color:#666;font:24px "微软雅黑","Microsoft YaHei",sans-serif;margin:30px 0 0 0;padding:0;padding-bottom:7px}#error-page{margin-top:50px}h3{text-align:center}#error-page p{font-size:9px;line-height:1.5;margin:25px 0 20px}#error-page code{font-family:Consolas,Monaco,monospace}ul li{margin-bottom:10px;font-size:9px}a{color:#21759B;text-decoration:none;margin-top:-10px}a:hover{color:#D54E21}.button{background:#f7f7f7;border:1px solid #ccc;color:#555;display:inline-block;text-decoration:none;font-size:9px;line-height:26px;height:28px;margin:0;padding:0 10px 1px;cursor:pointer;-webkit-border-radius:3px;-webkit-appearance:none;border-radius:3px;white-space:nowrap;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;-webkit-box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);vertical-align:top}.button.button-large{height:29px;line-height:28px;padding:0 12px}.button:focus,.button:hover{background:#fafafa;border-color:#999;color:#222}.button:focus{-webkit-box-shadow:1px 1px 1px rgba(0,0,0,.2);box-shadow:1px 1px 1px rgba(0,0,0,.2)}.button:active{background:#eee;border-color:#999;color:#333;-webkit-box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5);box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5)}table{table-layout:auto;border:1px solid #333;empty-cells:show;border-collapse:collapse}th{padding:4px;border:1px solid #333;overflow:hidden;color:#333;background:#eee}td{padding:4px;border:1px solid #333;overflow:hidden;color:#333}
        </style>
    </head>
    <body id="error-page">
        <?php echo '<h3>'.$title.'</h3>';
        echo $msg; ?>
    </body>
    </html>
    <?php
    exit;
}

function config_get($key, $default = null)
{
    $value = config('sys.'.$key);
    return $value ?: $default;
}

function config_set($key, $value)
{
    $res = Db::name('config')->replace()->insert(['key'=>$key, 'value'=>$value]);
    return $res!==false;
}

function get_host($url){
	$arr=parse_url($url);
	return $arr['host'];
}
function get_main_host($url){
	$arr=parse_url($url);
	$host = $arr['host'];
	if(substr_count($host, '.')>1){
		$host = substr($host, strpos($host, '.')+1);
	}
	return $host;
}

function checkDomain($domain){
	if(empty($domain) || !preg_match('/^[-$a-z0-9_*.]{2,512}$/i', $domain) || (stripos($domain, '.') === false) || substr($domain, -1) == '.' || substr($domain, 0 ,1) == '.' || substr($domain, 0 ,1) == '*' && substr($domain, 1 ,1) != '.' || substr_count($domain, '*')>1 || strpos($domain, '*')>0 || strlen($domain)<4) return false;
	return true;
}

function getSubstr($str, $leftStr, $rightStr)
{
	$left = strpos($str, $leftStr);
	$start = $left+strlen($leftStr);
	$right = strpos($str, $rightStr, $start);
	if($left < 0) return '';
	if($right>0){
		return substr($str, $start, $right-$start);
	}else{
		return substr($str, $start);
	}
}

function checkRefererHost(){
    if(!request()->header('referer'))return false;
    $url_arr = parse_url(request()->header('referer'));
    $http_host = request()->header('host');
    if(strpos($http_host,':'))$http_host = substr($http_host, 0, strpos($http_host, ':'));
    return $url_arr['host'] === $http_host;
}

function checkIfActive($string) {
	$array=explode(',',$string);
	$action = request()->action();
	if (in_array($action,$array)){
		return 'active';
	}else
		return null;
}

function refresh_wx_access_token($id, $force = false){
	Db::startTrans();
	$access_token = null;
	try{
		$row = Db::name('token')->where('id', $id)->lock(true)->find();
		if(!$row) throw new Exception('记录不存在');
		if($row['access_token'] && strtotime($row['expiretime']) - 200 >= time() && !$force){
			Db::rollback();
			return [$row['access_token'], strtotime($row['expiretime']) - time()];
		}
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$row['appid']."&secret=".$row['appsecret'];
		$output = get_curl($url);
		$res = json_decode($output, true);
		if (isset($res['access_token'])) {
			$access_token = $res['access_token'];
			$expire_time = time() + $res['expires_in'];
			Db::name('token')->where('id', $id)->update(['access_token'=>$res['access_token'], 'updatetime'=>date("Y-m-d H:i:s"), 'expiretime'=>date("Y-m-d H:i:s", $expire_time)]);
		}elseif(isset($res['errmsg'])){
			throw new Exception('AccessToken获取失败：'.$res['errmsg']);
		}else{
			throw new Exception('AccessToken获取失败');
		}
		Db::commit();
		return [$access_token, $res['expires_in']];
	} catch (\Exception $e) {
		Db::rollback();
		throw new Exception($e->getMessage());
	}
}

function refresh_qywx_access_token($id, $force = false){
	Db::startTrans();
	$access_token = null;
	try{
		$row = Db::name('token')->where('id', $id)->lock(true)->find();
		if(!$row) throw new Exception('记录不存在');
		if($row['access_token'] && strtotime($row['expiretime']) - 200 >= time() && !$force){
			Db::rollback();
			return [$row['access_token'], strtotime($row['expiretime']) - time()];
		}
		$url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=".$row['appid']."&corpsecret=".$row['appsecret'];
		$output = get_curl($url);
		$res = json_decode($output, true);
		if (isset($res['access_token'])) {
			$access_token = $res['access_token'];
			$expire_time = time() + $res['expires_in'];
			Db::name('token')->where('id', $id)->update(['access_token'=>$res['access_token'], 'updatetime'=>date("Y-m-d H:i:s"), 'expiretime'=>date("Y-m-d H:i:s", $expire_time)]);
		}elseif(isset($res['errmsg'])){
			throw new Exception('AccessToken获取失败：'.$res['errmsg']);
		}else{
			throw new Exception('AccessToken获取失败');
		}
		Db::commit();
		return [$access_token, $res['expires_in']];
	} catch (\Exception $e) {
		Db::rollback();
		throw new Exception($e->getMessage());
	}
}
