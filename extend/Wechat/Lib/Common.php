<?php

namespace Wechat\Lib;

use Prpcrypt;
use Wechat\Loader;

/**
 * WeChat SDK basic class
 *
 * @category WechatSDK
 * @subpackage library
 * @author Anyon <zoujingli@qq.com>
 * @date 2016/05/28 11:55
 */
class Common {

    /** API interface URL needs to use this prefix */
    const API_BASE_URL_PREFIX = 'https://api.weixin.qq.com';
    const API_URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin';
    const GET_TICKET_URL = '/ticket/getticket?';
    const AUTH_URL = '/token?grant_type=client_credential&';
    public $token;
    public $encodingAesKey;
    public $encrypt_type;
    public $appid;
    public $appsecret;
    public $access_token;
    public $postxml;
    public $_msg;
    public $errCode = 0;
    public $errMsg = "";
    public $config = array();
    private $_retry = FALSE;

    /**
     * Construction method
     * @param array $options
     */
    public function __construct($options = array()) {
        $config = Loader::config($options);
        $this->token = isset($config['token']) ? $config['token'] : '';
        $this->appid = isset($config['appid']) ? $config['appid'] : '';
        $this->appsecret = isset($config['appsecret']) ? $config['appsecret'] : '';
        $this->encodingAesKey = isset($config['encodingaeskey']) ? $config['encodingaeskey'] : '';
        $this->config = $config;
    }

    /**
     * Interface verification
     * @return bool
     */
    public function valid() {
        $encryptStr = "";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $postStr = file_get_contents("php://input");
            $array = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->encrypt_type = isset($_GET["encrypt_type"]) ? $_GET["encrypt_type"] : '';
            if ($this->encrypt_type == 'aes') {
                $encryptStr = $array['Encrypt'];
                !class_exists('Prpcrypt', FALSE) && require __DIR__ . '/Prpcrypt.php';
                $pc = new Prpcrypt($this->encodingAesKey);
                $array = $pc->decrypt($encryptStr, $this->appid);
                if (!isset($array[0]) || intval($array[0]) > 0) {
                    $this->errCode = $array[0];
                    $this->errMsg = $array[1];
                    Tools::log("Interface Authentication Failed. {$this->errMsg}[{$this->errCode}]", 'ERR');
                    return false;
                }
                $this->postxml = $array[1];
                empty($this->appid) && $this->appid = $array[2];
            } else {
                $this->postxml = $postStr;
            }
        } elseif (isset($_GET["echostr"])) {
            if ($this->checkSignature()) {
                exit($_GET["echostr"]);
            } else {
                return false;
            }
        }
        if (!$this->checkSignature($encryptStr)) {
            $this->errMsg = 'Interface authentication failed, please use the correct method to call.';
            return false;
        }
        return true;
    }

    /**
    * Verify from WeChat server
     * @param string $str
     * @return bool
     */
    private function checkSignature($str = '') {
        // If there is encrypted authentication, use the encrypted authentication segment
        $signature = isset($_GET["msg_signature"]) ? $_GET["msg_signature"] : (isset($_GET["signature"]) ? $_GET["signature"] : '');
        $timestamp = isset($_GET["timestamp"]) ? $_GET["timestamp"] : '';
        $nonce = isset($_GET["nonce"]) ? $_GET["nonce"] : '';
        $tmpArr = array($this->token, $timestamp, $nonce, $str);
        sort($tmpArr, SORT_STRING);
        if (sha1(implode($tmpArr)) == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get public account access access_token
     * @param string $appid can be empty if provided during class initialization
     * @param string $appsecret can be empty if provided at class initialization
     * @param string $token Manually specify access_token, it is not recommended to use it if it is not necessary
     * @return bool|string
     */
    public function getAccessToken($appid = '', $appsecret = '', $token = '') {
        if (!$appid || !$appsecret) {
            $appid = $this->appid;
            $appsecret = $this->appsecret;
        }
        if ($token) {
            return $this->access_token = $token;
        }
        $cache = 'wechat_access_token_' . $appid;
        if (($access_token = Tools::getCache($cache)) && !empty($access_token)) {
            return $this->access_token = $access_token;
        }
        # Detection event registration
        if (isset(Loader::$callback[__FUNCTION__])) {
            return $this->access_token = call_user_func_array(Loader::$callback[__FUNCTION__], array(&$this, &$cache));
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::AUTH_URL . 'appid=' . $appid . '&secret=' . $appsecret);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                Tools::log("Get New AccessToken Error. {$this->errMsg}[{$this->errCode}]", 'ERR');
                return false;
            }
            $this->access_token = $json['access_token'];
            Tools::log("Get New AccessToken Success.");
            Tools::setCache($cache, $this->access_token, 5000);
            return $this->access_token;
        }
        return false;
    }

    /**
     * interface failure retry
     * @param $method SDK method name
     * @param array $arguments SDK method parameters
     * @return bool|mixed
     */
    protected function checkRetry($method, $arguments = array()) {
        if (!$this->_retry && in_array($this->errCode, array('40014', '40001', '41001', '42001'))) {
            Tools::log("Run {$method} Faild. {$this->errMsg}[{$this->errCode}]", 'ERR');
            ($this->_retry = true) && $this->resetAuth();
            $this->errCode = 40001;
            $this->errMsg = 'no access';
            Tools::log("Retry Run {$method} ...");
            return call_user_func_array(array($this, $method), $arguments);
        }
        return false;
    }

    /**
     * delete verification data
     * @param string $appid can be empty if provided during class initialization
     * @return bool
     */
    public function resetAuth($appid = '') {
        $authname = 'wechat_access_token_' . (empty($appid) ? $this->appid : $appid);
        Tools::log("Reset Auth And Remove Old AccessToken.");
        $this->access_token = '';
        Tools::removeCache($authname);
        return true;
    }

}
