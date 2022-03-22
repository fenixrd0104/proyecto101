<?php

namespace Wechat;

use Wechat\Lib\Common;
use Wechat\Lib\Tools;
use Wechat\Loader;

/**
* WeChat front-end JavaScript signature SDK
 *
 * @author Anyon <zoujingli@qq.com>
 * @date 2016/06/28 11:24
 */
class WechatScript extends Common {

    /**
     * JSAPI authorized TICKET
     * @var string
     */
    public $jsapi_ticket;

    /**
     * Remove JSAPI authorization TICKET
     * @param string $appid
     * @return bool
     */
    public function resetJsTicket($appid = '') {
        $this->jsapi_ticket = '';
        $authname = 'wechat_jsapi_ticket_' . empty($appid) ? $this->appid : $appid;
        Tools::removeCache($authname);
        return true;
    }

    /**
     * Get JSAPI authorization TICKET
     * @param string $appid used for multiple appids, can be empty
     * @param string $jsapi_ticket Manually specify jsapi_ticket, it is not recommended to use it if it is not necessary
     * @param string $access_token Get jsapi_ticket specified access_token
     * @return bool|string
     */
    public function getJsTicket($appid = '', $jsapi_ticket = '', $access_token = '') {
        if (empty($access_token)) {
            if (!$this->access_token && !$this->getAccessToken()) {
                return false;
            }
            $access_token = $this->access_token;
        }
        if (empty($appid)) {
            $appid = $this->appid;
        }
        # Manually specify token, use first
        if ($jsapi_ticket) {
            $this->jsapi_ticket = $jsapi_ticket;
            return $this->jsapi_ticket;
        }
        # try to read from the cache
        $cache = 'wechat_jsapi_ticket_' . $appid;
        $jt = Tools::getCache($cache);
        if ($jt) {
            return $this->jsapi_ticket = $jt;
        }
        # detect event registration
        if (isset(Loader::$callback[__FUNCTION__])) {
            return $this->jsapi_ticket = call_user_func_array(Loader::$callback[__FUNCTION__], array(&$this, &$cache));
        }
        # Call the interface to get
        $result = Tools::httpGet(self::API_URL_PREFIX . self::GET_TICKET_URL . "access_token={$access_token}" . '&type=jsapi');
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            $this->jsapi_ticket = $json['ticket'];
            Tools::setCache($cache, $this->jsapi_ticket, $json['expires_in'] ? intval($json['expires_in']) - 100 : 3600);
            return $this->jsapi_ticket;
        }
        return false;
    }

    /**
     * Get JsApi usage signature
     * @param string $url The URL of the web page, # and its following parts are automatically processed
     * @param int $timestamp current timestamp (automatically generated if empty)
     * @param string $noncestr random string (automatically generated if empty)
     * @param string $appid used for multiple appids, can be empty
     * @param string $access_token Get jsapi_ticket specified access_token
     * @return array|bool returns the signature string
     */
    public function getJsSign($url, $timestamp = 0, $noncestr = '', $appid = '', $access_token = '') {
        if (!$this->jsapi_ticket && !$this->getJsTicket($appid, '', $access_token) || empty($url)) {
            return false;
        }
        $data = array(
            "jsapi_ticket" => $this->jsapi_ticket,
            "timestamp"    => empty($timestamp) ? time() : $timestamp,
            "noncestr"     => '' . empty($noncestr) ? Tools::createNoncestr(16) : $noncestr,
            "url"          => trim($url),
        );
        return array(
            "url"       => $url,
            'debug'     => false,
            "appId"     => empty($appid) ? $this->appid : $appid,
            "nonceStr"  => $data['noncestr'],
            "timestamp" => $data['timestamp'],
            "signature" => Tools::getSignature($data, 'sha1'),
            'jsApiList' => array(
                'onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ', 'onMenuShareWeibo', 'onMenuShareQZone',
                'hideOptionMenu', 'showOptionMenu', 'hideMenuItems', 'showMenuItems', 'hideAllNonBaseMenuItem', 'showAllNonBaseMenuItem',
                'chooseImage', 'previewImage', 'uploadImage', 'downloadImage', 'closeWindow', 'scanQRCode', 'chooseWXPay',
                'translateVoice', 'getNetworkType', 'openLocation', 'getLocation',
                'openProductSpecificView', 'addCard', 'chooseCard', 'openCard',
                'startRecord', 'stopRecord', 'onVoiceRecordEnd', 'playVoice', 'pauseVoice', 'stopVoice', 'onVoicePlayEnd', 'uploadVoice', 'downloadVoice',
            )
        );
    }

}
