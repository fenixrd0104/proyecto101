<?php

namespace Wechat;

use Wechat\Lib\Common;
use Wechat\Lib\Tools;

/**
 * WeChat webpage authorization
 */
class WechatOauth extends Common {

    const OAUTH_PREFIX = 'https://open.weixin.qq.com/connect/oauth2';
    const OAUTH_AUTHORIZE_URL = '/authorize?';
    const OAUTH_TOKEN_URL = '/sns/oauth2/access_token?';
    const OAUTH_REFRESH_URL = '/sns/oauth2/refresh_token?';
    const OAUTH_USERINFO_URL = '/sns/userinfo?';
    const OAUTH_AUTH_URL = '/sns/auth?';

    /**
     * Oauth authorization jump interface
     * @param string $callback authorization jumpback address
     * @param string $state is the state parameter after redirection (fill in the parameter value of a-zA-Z0-9, up to 128 bytes)
     * @param string $scope authorization class type (optional value snsapi_base|snsapi_userinfo)
     * @return string
     */
    public function getOauthRedirect($callback, $state = '', $scope = 'snsapi_base') {
        $redirect_uri = urlencode($callback);
        return self::OAUTH_PREFIX . self::OAUTH_AUTHORIZE_URL . "appid={$this->appid}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}#wechat_redirect";
    }

    /**
     * Get AccessToken and openid through code
     * @return bool|array
     */
    public function getOauthAccessToken() {
        $code = isset($_GET['code']) ? $_GET['code'] : '';
        if (empty($code)) {
            Tools::log("getOauthAccessToken Fail, Because there is no access to the code value in get.");
            return false;
        }
        $result = Tools::httpGet(self::API_BASE_URL_PREFIX . self::OAUTH_TOKEN_URL . "appid={$this->appid}&secret={$this->appsecret}&code={$code}&grant_type=authorization_code");
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                Tools::log("WechatOauth::getOauthAccessToken Fail.{$this->errMsg} [{$this->errCode}]", 'ERR');
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
    * Refresh access token and renew
     * @param string $refresh_token
     * @return bool|array
     */
    public function getOauthRefreshToken($refresh_token) {
        $result = Tools::httpGet(self::API_BASE_URL_PREFIX . self::OAUTH_REFRESH_URL . "appid={$this->appid}&grant_type=refresh_token&refresh_token={$refresh_token}");
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                Tools::log("WechatOauth::getOauthRefreshToken Fail.{$this->errMsg} [{$this->errCode}]", 'ERR');
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * Obtain authorized user information
     * @param string $access_token
     * @param string $openid
     * @return bool|array {openid,nickname,sex,province,city,country,headimgurl,privilege,[unionid]}
     * Note: The unionid field will only appear after the user binds the official account to the WeChat Open Platform account. It is recommended to check with isset() before calling
     */
    public function getOauthUserInfo($access_token,$openid) {
        $result = Tools::httpGet(self::API_BASE_URL_PREFIX . self::OAUTH_USERINFO_URL . "access_token={$access_token}&openid={$openid}"."&lang=zh_CN");
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                Tools::log("WechatOauth::getOauthUserInfo Fail.{$this->errMsg} [{$this->errCode}]", 'ERR');
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * Verify that the authorization certificate is valid
     * @param string $access_token
     * @param string $openid
     * whether @return bool is valid
     */
    public function getOauthAuth($access_token, $openid) {
        $result = Tools::httpGet(self::API_BASE_URL_PREFIX . self::OAUTH_AUTH_URL . "access_token={$access_token}&openid={$openid}");
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                Tools::log("WechatOauth::getOauthAuth Fail.{$this->errMsg} [{$this->errCode}]", 'ERR');
                return false;
            } else if ($json['errcode'] == 0) {
                return true;
            }
        }
        return false;
    }

}
