<?php

namespace Wechat;

use Wechat\Lib\Common;
use Wechat\Lib\Tools;

/**
 * WeChat fan operation SDK
  *
  * @author Anyon <zoujingli@qq.com>
  * @date 2016/06/28 11:20
  */
 class WechatUser extends Common {
 
     /** Get the list of followers */
     const USER_GET_URL = '/user/get?';
     /* Get fan information */
     const USER_INFO_URL = '/user/info?';
     /* Get fan information in batches */
     const USER_BATCH_INFO_URL = '/user/info/batchget?';
     /* Update fan tag */
     const USER_UPDATEREMARK_URL = '/user/info/updateremark?';
 
     /** create label */
     const TAGS_CREATE_URL = '/tags/create?';
     /* Get the list of tags */
     const TAGS_GET_URL = '/tags/get?';
     /* update labels */
     const TAGS_UPDATE_URL = '/tags/update?';
     /* delete tag */
     const TAGS_DELETE_URL = '/tags/delete?';
     /* Get the list of followers under the tag */
     const TAGS_GET_USER_URL = '/user/tag/get?';
     /* Batch tag fans */
     const TAGS_MEMBER_BATCHTAGGING = '/tags/members/batchtagging?';
     /* Untag fans in batches */
     const TAGS_MEMBER_BATCHUNTAGGING = '/tags/members/batchuntagging?';
     /* Get the tag list of fans */
    const TAGS_LIST = '/tags/getidlist?';

    /** Get the group list */
        const GROUP_GET_URL = '/groups/get?';
        /* Get the group the fan is in */
        const USER_GROUP_URL = '/groups/getid?';
        /* create group */
        const GROUP_CREATE_URL = '/groups/create?';
        /* update group */
        const GROUP_UPDATE_URL = '/groups/update?';
        /* delete group */
        const GROUP_DELETE_URL = '/groups/delete?';
        /* Modify the group of fans */
        const GROUP_MEMBER_UPDATE_URL = '/groups/members/update?';
        /* Batch modify the group of fans */
        const GROUP_MEMBER_BATCHUPDATE_URL = '/groups/members/batchupdate?';
    
        /** Get the blacklist list */
        const BACKLIST_GET_URL = '/tags/members/getblacklist?';
        /* Block fans in batches */
        const BACKLIST_ADD_URL = '/tags/members/batchblacklist?';
        /* Unblock fans in batches */
        const BACKLIST_DEL_URL = '/tags/members/batchunblacklist?';
    
        /**
         * Get a list of followers in batches
     * @param string $next_openid
     * @return bool|array
     */
    public function getUserList($next_openid = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::USER_GET_URL . "access_token={$this->access_token}" . '&next_openid=' . $next_openid);
        if ($result) {
            $json = json_decode($result, true);
            if (isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Get follower details
     * @param string $openid
     * @return bool|array {subscribe,openid,nickname,sex,city,province,country,language,headimgurl,subscribe_time,[unionid]}
     * @Note: The unionid field will only appear after fans bind the official account to the WeChat Open Platform account. It is recommended to check with isset() before calling
     */
    public function getUserInfo($openid) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::USER_INFO_URL . "access_token={$this->access_token}&openid={$openid}");
        if ($result) {
            $json = json_decode($result, true);
            if (isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Get basic user information in batches
     * @param array $openids User oepnid list (support up to 100 openids)
     * @param string $lang specifies the return language
     * @return bool|mixed
     */
    public function getUserBatchInfo(array $openids, $lang = 'zh_CN') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('user_list' => array());
        foreach (array_unique($openids) as $openid) {
            $data['user_list'][] = array('openid' => $openid, 'lang' => $lang);
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::USER_BATCH_INFO_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (isset($json['errcode']) && !isset($json['user_info_list'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json['user_info_list'];
        }
        return false;
    }

    /**
     * Set the fan note name
     * @param string $openid
     * @param string $remark remark name
     * @return bool|array
     */
    public function updateUserRemark($openid, $remark) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('openid' => $openid, 'remark' => $remark);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::USER_UPDATEREMARK_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Get a list of fan groups
     * @return bool|array
     */
    public function getGroup() {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::GROUP_GET_URL . "access_token={$this->access_token}");
        if ($result) {
            $json = json_decode($result, true);
            if (isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * delete fan group
     * @param type $id
     * @return bool
     */
    public function delGroup($id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('group' => array('id' => $id));
        $result = Tools::httpPost(self::API_URL_PREFIX . self::GROUP_DELETE_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
    * Get the group of fans
     * @param string $openid
     * @return bool|int If successful, return the fan group id
     */
    public function getUserGroup($openid) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('openid' => $openid);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::USER_GROUP_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            } else if (isset($json['groupid'])) {
                return $json['groupid'];
            }
        }
        return false;
    }

    /**
     * Added custom grouping
     * @param string $name group name
     * @return bool|array
     */
    public function createGroup($name) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('group' => array('name' => $name));
        $result = Tools::httpPost(self::API_URL_PREFIX . self::GROUP_CREATE_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Change group name
     * @param int $groupid group id
     * @param string $name group name
     * @return bool|array
     */
    public function updateGroup($groupid, $name) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('group' => array('id' => $groupid, 'name' => $name));
        $result = Tools::httpPost(self::API_URL_PREFIX . self::GROUP_UPDATE_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Mobile fan grouping
     * @param int $groupid group id
     * @param string $openid fan openid
     * @return bool|array
     */
    public function updateGroupMembers($groupid, $openid) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('openid' => $openid, 'to_groupid' => $groupid);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::GROUP_MEMBER_UPDATE_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Bulk move fan groups
     * @param string $groupid group ID
     * @param string $openid_list fan openid array (no more than 50 at a time)
     * @return bool|array
     */
    public function batchUpdateGroupMembers($groupid, $openid_list) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('openid_list' => $openid_list, 'to_groupid' => $groupid);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::GROUP_MEMBER_BATCHUPDATE_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
    * Added custom tags
     * @param string $name tag name
     * @return bool|array
     */
    public function createTags($name) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('tag' => array('name' => $name));
        $result = Tools::httpPost(self::API_URL_PREFIX . self::TAGS_CREATE_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
    * Update labels
     * @param string $id tag id
     * @param string $name tag name
     * @return bool|array
     */
    public function updateTag($id, $name) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('tag' => array('id' => $id, 'name' => $name));
        $result = Tools::httpPost(self::API_URL_PREFIX . self::TAGS_UPDATE_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Get a list of fan tags
     * @return bool|array
     */
    public function getTags() {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::TAGS_GET_URL . "access_token={$this->access_token}");
        if ($result) {
            $json = json_decode($result, true);
            if (isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * remove fan tag
     * @param string $id
     * @return bool
     */
    public function delTag($id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('tag' => array('id' => $id));
        $result = Tools::httpPost(self::API_URL_PREFIX . self::TAGS_DELETE_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Get a list of followers under a tag
     * @param string $tagid
     * @param string $next_openid
     * @return bool
     */
    public function getTagUsers($tagid, $next_openid = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('tagid' => $tagid, 'next_openid' => $next_openid);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::TAGS_GET_USER_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Batch tag fans
     * @param string $tagid Tag ID
     * @param array $openid_list Fan openid array, no more than 50 at a time
     * @return bool|array
     */
    public function batchAddUserTag($tagid, $openid_list) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('openid_list' => $openid_list, 'tagid' => $tagid);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::TAGS_MEMBER_BATCHTAGGING . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Bulk untags for fans
     * @param string $tagid Tag ID
     * @param array $openid_list Fan openid array, no more than 50 at a time
     * @return bool|array
     */
    public function batchDeleteUserTag($tagid, $openid_list) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('openid_list' => $openid_list, 'tagid' => $tagid);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::TAGS_MEMBER_BATCHUNTAGGING . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Get a list of followers' tags
     * @param string $openid fan openid
     * @return bool|array
     */
    public function getUserTags($openid) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('openid' => $openid);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::TAGS_LIST . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !isset($json['tagid_list']) || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json['tagid_list'];
        }
        return false;
    }

    /**
     * Get blacklist fans in batches
     * @param string $begin_openid
     * @return bool|array
     */
    public function getBacklist($begin_openid = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = empty($begin_openid) ? array() : array('begin_openid' => $begin_openid);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::BACKLIST_GET_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Block black fans in batches
     * @param string $openids
     * @return bool|array
     */
    public function addBacklist($openids) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('openid_list' => $openids);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::BACKLIST_ADD_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Unblock fans in batches
     * @param string $openids
     * @return bool|array
     */
    public function delBacklist($openids) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('openid_list' => $openids);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::BACKLIST_DEL_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
