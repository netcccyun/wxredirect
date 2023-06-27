<?php
namespace app\lib;

class WechatCrypt
{
    private $key;
    private $iv;
    private static $block_size = 32;

    public function __construct($enckey)
    {
        $this->key = base64_decode($enckey . '=');
        $this->iv = substr($this->key, 0, 16);
    }

    public function encrypt($data, $appid)
    {
        $str = $this->getRandomStr() . pack('N', strlen($data)) . $data . $appid;
        $str = $this->enPKSC7($str);
        $encrypted = openssl_encrypt($str, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
        return $encrypted;
    }

    public function decrypt($data)
    {
        $decrypted = openssl_decrypt($data, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
        if(!$decrypted) return false;
        $decrypted = $this->dePKSC7($decrypted);

        $content = substr($decrypted, 16, strlen($decrypted));
        $len_list = unpack('N', substr($content, 0, 4));
        $xml_len = $len_list[1];
        $xml_content = substr($content, 4, $xml_len);
        $from_appid = substr($content, $xml_len + 4);

        return $xml_content;
    }

    private function enPKSC7($text)
    {
        $block_size = self::$block_size; //128:16、256:32
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = $block_size - ($text_length % $block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = $block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }
    
    private function dePKSC7($text)
    {
        $block_size = self::$block_size; //128:16、256:32
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > $block_size) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

    private function getRandomStr()
    {
        $str = '';
        $str_pol = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyl';
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}