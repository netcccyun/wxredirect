<?php
namespace app\lib;

use Exception;

class WechatServer
{
    private $token;
    private $crypt;

    public function __construct($token, $enckey)
    {
        $this->token = $token;
        if($enckey) $this->crypt = new WechatCrypt($enckey);
    }

	//验证此次请求的签名信息
	private function verifySignature()
	{
		if (!(isset($_GET['signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']))) {
			return false;
		}

        $signature = $this->getSignature($_GET['timestamp'], $_GET['nonce']);

		return $signature === $_GET['signature'];
	}

    //验证消息签名
    private function verifyMsgSignature($msg_encrypt)
    {
        if (!(isset($_GET['msg_signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']))) {
			return false;
		}

        $signature = $this->getSignature($_GET['timestamp'], $_GET['nonce'], $msg_encrypt);

		return $signature === $_GET['msg_signature'];
    }

    //生成SHA1签名
    private function getSignature($timestamp, $nonce, $encrypt_msg = null)
    {
        $signatureArray = array($this->token, $timestamp, $nonce, $encrypt_msg);
		sort($signatureArray, SORT_STRING);
        return sha1(implode($signatureArray));
    }

    //获取微信请求内容
	public function getWechatRequest()
	{
		if (!$this->verifySignature()) {
			exit('签名验证失败');
		}
        
        // 网址接入验证
        if (isset($_GET['echostr'])) {
			exit($_GET['echostr']);
		}

        $xml = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
        if (!$xml) {
			exit('缺少数据');
		}

        return $xml;
	}

    //获取企业微信请求内容
	public function getWeWorkRequest()
	{
        // 网址接入验证
        if (isset($_GET['echostr'])) {
			if (!$this->verifyMsgSignature($_GET['echostr'])) {
                exit('签名验证失败');
            }
            $msg = $this->crypt->decrypt($_GET['echostr']);
            if(!$msg) exit('消息解密失败');
            exit($msg);
		}

        $xml = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
        if (!$xml) {
			exit('缺少数据');
		}

        return $xml;
	}
    
    //发送微信请求内容
    public function sendRequest($url, $xml)
    {
        $params = input('get.');
        $url = $url . '?' . http_build_query($params);
        
        try{
            $response = $this->curl($url, $xml);
        }catch(Exception $e){
            return [false, $e->getMessage()];
        }

        if(strpos($response, 'success')!==false || strpos($response, '<xml')!==false || strpos($response, 'ok')!==false || empty($response)){
            return [true, $response];
        }else{
            return [false, $response];
        }
    }

    //验证微信服务器
    public function verifyWechatServer($url)
    {
        $echostr = random(18, 1);
        $params['echostr'] = $echostr;
        $params['nonce'] = random(10, 1);
        $params['timestamp'] = time();
        $params['signature'] = $this->getSignature($params['timestamp'], $params['nonce']);
        $url = $url . '?' . http_build_query($params);

        try{
            $response = $this->curl($url);
        }catch(Exception $e){
            return false;
        }
        
        if(strpos($response, $echostr)!==false) {
            return true;
        }
        return false;
    }

    //验证企业微信服务器
    public function verifyWeWorkServer($url)
    {
        return true;
    }

    private function curl($url, $xml = null, $timeout = 3)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader[] = "Accept: */*";
        $httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
        $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
        $httpheader[] = "Cache-Control: no-cache";
        $httpheader[] = "Pragma: no-cache";
        $httpheader[] = "Connection: close";
        if($xml){
            $httpheader[] = "Content-Type: text/xml";
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.0.4; es-mx; HTC_One_X Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0");
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            $errmsg = curl_error($ch);
            curl_close($ch);
            throw new Exception($errmsg);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($httpCode >= 300){
            throw new Exception('httpCode='.$httpCode);
        }
        return $response;
    }
}