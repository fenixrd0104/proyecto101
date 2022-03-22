<?php

namespace Wechat;

use Wechat\Lib\Common;
use Wechat\Lib\Tools;

/**
 * WeChat menu operation SDK
 *
 * @author Anyon <zoujingli@qq.com>
 * @date 2016/06/28 11:52
 */
class WechatMenu extends Common {

    /** Create custom menu */
    const MENU_ADD_URL = '/menu/create?';
    /* Get custom menu */
    const MENU_GET_URL = '/menu/get?';
    /* delete custom menu */
    const MENU_DEL_URL = '/menu/delete?';

    /** Add custom menu */
    const COND_MENU_ADD_URL = '/menu/addconditional?';
    /* delete custom menu */
    const COND_MENU_DEL_URL = '/menu/delconditional?';
    /* Test personality menu */
    const COND_MENU_TRY_URL = '/menu/trymatch?';

    /**
     * Create custom menus
     * @param array $data menu array data
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141013&token=&lang=zh_CN Documentation
     * @return bool
     */
    public function createMenu($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MENU_ADD_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return true;
        }
        return false;
    }

    /**
     * get all menus
     * @return bool|array
     */
    public function getMenu() {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::MENU_GET_URL . "access_token={$this->access_token}");
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * delete all menus
     * @return bool
     */
    public function deleteMenu() {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::MENU_DEL_URL . "access_token={$this->access_token}");
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return true;
        }
        return false;
    }

    /**
     * Create personalized menus
     * @param array $data menu array data
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1455782296&token=&lang=zh_CN Documentation
     * @return bool|string
     */
    public function createCondMenu($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::COND_MENU_ADD_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode']) || empty($json['menuid'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json['menuid'];
        }
        return false;
    }

    /**
     * Remove personalized menu
     * @param string $menuid menu ID
     * @return bool
     */
    public function deleteCondMenu($menuid) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('menuid' => $menuid);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::COND_MENU_DEL_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return true;
        }
        return false;
    }

    /**
     * Test and return to personalization menu
     * @param string $openid fan openid
     * @return bool
     */
    public function tryCondMenu($openid) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('user_id' => $openid);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::COND_MENU_TRY_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

}
