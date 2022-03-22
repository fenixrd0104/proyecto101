<?php

namespace Wechat;

use Wechat\Lib\Common;
use Wechat\Lib\Tools;

class WechatCustom extends Common {

    /** Multiple customer service related addresses */
    const CUSTOM_SERVICE_GET_RECORD = '/customservice/getrecord?';
    const CUSTOM_SERVICE_GET_KFLIST = '/customservice/getkflist?';
    const CUSTOM_SERVICE_GET_ONLINEKFLIST = '/customservice/getonlinekflist?';
    const CUSTOM_SESSION_CREATE = '/customservice/kfsession/create?';
    const CUSTOM_SESSION_CLOSE = '/customservice/kfsession/close?';
    const CUSTOM_SESSION_SWITCH = '/customservice/kfsession/switch?';
    const CUSTOM_SESSION_GET = '/customservice/kfsession/getsession?';
    const CUSTOM_SESSION_GET_LIST = '/customservice/kfsession/getsessionlist?';
    const CUSTOM_SESSION_GET_WAIT = '/customservice/kfsession/getwaitcase?';
    const CS_KF_ACCOUNT_ADD_URL = '/customservice/kfaccount/add?';
    const CS_KF_ACCOUNT_UPDATE_URL = '/customservice/kfaccount/update?';
    const CS_KF_ACCOUNT_DEL_URL = '/customservice/kfaccount/del?';
    const CS_KF_ACCOUNT_UPLOAD_HEADIMG_URL = '/customservice/kfaccount/uploadheadimg?';

    /**
     * Get multiple customer service session records
     * @param array $data data structure {"starttime":123456789,"endtime":987654321,"openid":"OPENID","pagesize":10,"pageindex":1,}
     * @return bool|array
     */
    public function getCustomServiceMessage($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::CUSTOM_SERVICE_GET_RECORD . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * Get the basic information of multi-customer service
     *
     * @return bool|array
     */
    public function getCustomServiceKFlist() {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::CUSTOM_SERVICE_GET_KFLIST . "access_token={$this->access_token}");
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
     * Get multi-customer service online customer service reception information
     *
     * @return bool|array
     */
    public function getCustomServiceOnlineKFlist() {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::CUSTOM_SERVICE_GET_ONLINEKFLIST . "access_token={$this->access_token}");
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
    * Create a designated multi-customer service session
     * @tutorial will fail when the user has been received by other customer service or the designated customer service is not online
     * @param string $openid //User openid
     * @param string $kf_account //Customer service account
     * @param string $text //Additional information, the text will be displayed on the customer service staff's multi-customer customer service client, it can be empty
     * @return bool|array
     */
    public function createKFSession($openid, $kf_account, $text = '') {
        $data = array("openid" => $openid, "kf_account" => $kf_account);
        if ($text) {
            $data["text"] = $text;
        }
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CUSTOM_SESSION_CREATE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Close the specified multi-customer service session
     * @tutorial will fail when the user is received by another customer service
     * @param string $openid //User openid
     * @param string $kf_account //Customer service account
     * @param string $text //Additional information, the text will be displayed on the customer service staff's multi-customer customer service client, it can be empty
     * @return bool | array //successfully return json array
     * {
     *   "errcode": 0,
     *   "errmsg": "ok",
     * }
     */
    public function closeKFSession($openid, $kf_account, $text = '') {
        $data = array("openid" => $openid, "kf_account" => $kf_account);
        if ($text) {
            $data["text"] = $text;
        }
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CUSTOM_SESSION_CLOSE . "access_token={$this->access_token}", Tools::json_encode($data));
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
    * Get user session state
     * @param string $openid //User openid
     * @return bool | array //successfully return json array
     * {
     * "errcode" : 0,
     * "errmsg" : "ok",
     * "kf_account" : "test1@test", //The customer service being received
     * "createtime": 123456789, //Session access time
     *  }
     */
    public function getKFSession($openid) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_BASE_URL_PREFIX . self::CUSTOM_SESSION_GET . "access_token={$this->access_token}" . '&openid=' . $openid);
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
     * Get the session list of the specified customer service
     * @param string $kf_account //User openid
     * @return bool | array //successfully return json array
     *array(
     * 'sessionlist' => array (
     * array (
     * 'openid'=>'OPENID', //customer openid
     * 'createtime'=>123456789, //Session creation time, UNIX timestamp
     * ),
     * array (
     * 'openid'=>'OPENID', //customer openid
     * 'createtime'=>123456789, //Session creation time, UNIX timestamp
     *         ),
     *     )
     *  )
     */
    public function getKFSessionlist($kf_account) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_BASE_URL_PREFIX . self::CUSTOM_SESSION_GET_LIST . "access_token={$this->access_token}" . '&kf_account=' . $kf_account);
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
     * Get a list of disconnected sessions
     * @return bool|array
     */
    public function getKFSessionWait() {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_BASE_URL_PREFIX . self::CUSTOM_SESSION_GET_WAIT . "access_token={$this->access_token}");
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
     * Add customer service account
     *
     * @param string $account The complete customer service account (account prefix @ official account WeChat, account prefix up to 10 characters)
     * @param string $nickname Customer service nickname, up to 6 Chinese characters or 12 English characters
     * @param string $password The plaintext login password of the customer service account, which will be automatically encrypted
     * @return bool|array
     */
    public function addKFAccount($account, $nickname, $password) {
        $data = array("kf_account" => $account, "nickname" => $nickname, "password" => md5($password));
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CS_KF_ACCOUNT_ADD_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Modify customer service account information
     *
     * @param string $account //Complete customer service account, the format is: account prefix@public account WeChat, the account prefix can be up to 10 characters, and must be English or numeric characters
     * @param string $nickname //Customer nickname, up to 6 Chinese characters or 12 English characters
     * @param string $password //The plaintext login password of the customer service account will be automatically encrypted
     * @return bool|array
     * return result successfully
     * {
     *   "errcode": 0,
     *   "errmsg": "ok",
     * }
     */
    public function updateKFAccount($account, $nickname, $password) {
        $data = array("kf_account" => $account, "nickname" => $nickname, "password" => md5($password));
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CS_KF_ACCOUNT_UPDATE_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Delete customer service account
          * @param string $account The complete customer service account (account prefix @ official account WeChat, account prefix up to 10 characters)
     * @return bool|array
     */
    public function deleteKFAccount($account) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_BASE_URL_PREFIX . self::CS_KF_ACCOUNT_DEL_URL . "access_token={$this->access_token}" . '&kf_account=' . $account);
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
     * Upload customer service avatar
     * @param string $account The complete customer service account (account prefix @ official account WeChat, account prefix up to 10 characters)
     * @param string $imgfile The full path of the avatar file, such as: 'D:\user.jpg'. The avatar file must be in JPG format, and the recommended pixel is 640*640
     * @return bool|array
     */
    public function setKFHeadImg($account, $imgfile) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CS_KF_ACCOUNT_UPLOAD_HEADIMG_URL . "access_token={$this->access_token}" . '&kf_account=' . $account, array('media' => '@' . $imgfile), true);
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
