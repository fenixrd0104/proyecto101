<?php

namespace Wechat;

use Wechat\Lib\Common;
use Wechat\Lib\Tools;

/**
* WeChat store interface
 * @author Anyon <zoujingli@qq.com>
 * @date 2016/10/26 15:43
 */
class WechatPoi extends Common {

    /** create store */
    const POI_ADD = '/cgi-bin/poi/addpoi?';

    /** Query store information */
    const POI_GET = '/cgi-bin/poi/getpoi?';

    /** Get the store list */
    const POI_GET_LIST = '/cgi-bin/poi/getpoilist?';

    /** Modify store information */
    const POI_UPDATE = '/cgi-bin/poi/updatepoi?';

    /** delete store */
    const POI_DELETE = '/cgi-bin/poi/delpoi?';

    /** Get store category list */
    const POI_CATEGORY = '/cgi-bin/poi/getwxcategory?';

    /**
     * Create a store
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444378120&token=&lang=zh_CN
     * @param array $data
     * @return bool
     */
    public function addPoi($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::POI_ADD . "access_token={$this->access_token}", Tools::json_encode($data));
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

    /**
     * delete store
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444378120&token=&lang=zh_CN
     * @param string $poi_id JSON data format
     * @return bool|array
     */
    public function delPoi($poi_id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('poi_id' => $poi_id);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::POI_DELETE . "access_token={$this->access_token}", Tools::json_encode($data));
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

    /**
     * Modify store service information
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444378120&token=&lang=zh_CN
     * @param array $data
     * @return bool
     */
    public function updatePoi($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::POI_UPDATE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Query store information
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444378120&token=&lang=zh_CN
     * @param string $poi_id
     * @return bool
     */
    public function getPoi($poi_id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('poi_id' => $poi_id);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::POI_GET . "access_token={$this->access_token}", Tools::json_encode($data));
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

    /**
    * Query store list
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444378120&token=&lang=zh_CN
     * @param int $begin start position, 0 is the query from the first
     * @param int $limit Returns the number of data, the maximum allowed is 50, the default is 20
     * @return bool|array
     */
    public function getPoiList($begin = 0, $limit = 50) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $limit > 50 && $limit = 50;
        $data = array('begin' => $begin, 'limit' => $limit);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::POI_GET_LIST . "access_token={$this->access_token}", Tools::json_encode($data));
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

    /**
     * Get a list of merchant store categories
     * @return bool|string
     */
    public function getCategory() {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::POI_CATEGORY . "access_token={$this->access_token}");
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
