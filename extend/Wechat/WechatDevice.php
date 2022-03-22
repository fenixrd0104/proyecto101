<?php

namespace Wechat;

use Wechat\Lib\Common;
use Wechat\Lib\Tools;

/**
 * Wechat device related SDK
 * @author Anyon <zoujingli@qq.com>
 * @date 2016-08-22 10:35
 */
class WechatDevice extends Common {

    const SHAKEAROUND_DEVICE_APPLYID = '/shakearound/device/applyid?'; //Apply for device ID
const SHAKEAROUND_DEVICE_APPLYSTATUS = '/shakearound/device/applystatus?'; //Query device ID application review status
    const SHAKEAROUND_DEVICE_UPDATE = '/shakearound/device/update?'; //Edit device information
    const SHAKEAROUND_DEVICE_SEARCH = '/shakearound/device/search?'; //Query the device list
    const SHAKEAROUND_DEVICE_BINDLOCATION = '/shakearound/device/bindlocation?'; //Configure the relationship between device and store ID
    const SHAKEAROUND_DEVICE_BINDPAGE = '/shakearound/device/bindpage?'; //Configure the binding relationship between the device and the page
    const SHAKEAROUND_MATERIAL_ADD = '/shakearound/material/add?'; //Upload Shakearound image material
    const SHAKEAROUND_PAGE_ADD = '/shakearound/page/add?'; //Add page
    const SHAKEAROUND_PAGE_UPDATE = '/shakearound/page/update?'; //Edit page
    const SHAKEAROUND_PAGE_SEARCH = '/shakearound/page/search?'; //Query page list
    const SHAKEAROUND_PAGE_DELETE = '/shakearound/page/delete?'; //Delete the page
    const SHAKEAROUND_USER_GETSHAKEINFO = '/shakearound/user/getshakeinfo?'; //Get device and user information around the shake
    const SHAKEAROUND_STATISTICS_DEVICE = '/shakearound/statistics/device?'; //Data statistics interface with device as dimension
    const SHAKEAROUND_STATISTICS_PAGE = '/shakearound/statistics/page?'; //Data statistics interface with page as dimension


    /**
     * Apply for device ID
     * @param array $data
     * @return bool|array
     */
    public function applyShakeAroundDevice($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_DEVICE_APPLYID . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Querying the Approval Status of Device ID Application
     * @param int $apply_id
     * @return bool|array
     */
    public function applyStatusShakeAroundDevice($apply_id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
		$data = array("apply_id" => $apply_id);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_DEVICE_APPLYSTATUS . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Edit device information
     * @param array $data
     * @return bool
     */
    public function updateShakeAroundDevice($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_DEVICE_UPDATE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Query device list
     * @param $data
     * @return bool|array
     */
    public function searchShakeAroundDevice($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_DEVICE_SEARCH . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Configure the relationship between the device and the store
     * @param string $device_id Device ID, if UUID, major, minor are filled in, the device ID can be omitted, if both are filled, the device ID will take precedence
     * @param int $poi_id Store ID to be associated
     * @param string $uuid UUID, major, minor, the three information must be filled in completely, if the device number is filled in, this information can be omitted
     * @param int $major
     * @param int $minor
     * @return bool|array
     */
    public function bindLocationShakeAroundDevice($device_id, $poi_id, $uuid = '', $major = 0, $minor = 0) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        if (!$device_id) {
            if (!$uuid || !$major || !$minor) {
                return false;
            }
            $device_identifier = array('uuid' => $uuid, 'major' => $major, 'minor' => $minor);
        } else {
            $device_identifier = array(
                'device_id' => $device_id
            );
        }
        $data = array('device_identifier' => $device_identifier, 'poi_id' => $poi_id);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_DEVICE_BINDLOCATION . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json; //this can be changed to return true
        }
        return false;
    }
    
    /**
     * Configure the relationship between the device and other official account stores
     * @param type $device_identifier device information
     * @param type $poi_id Store ID to be associated
     * @param type $poi_appid target WeChat appid
     * @return boolean
     */
    public function bindLocationOtherShakeAroundDevice($device_identifier,$poi_id,$poi_appid) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('device_identifier' => $device_identifier, 'poi_id' => $poi_id,"type"=>2,"poi_appid"=>$poi_appid);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_DEVICE_BINDLOCATION . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json; //this can be changed to return true
        }
        return false;
    }

    /**
     * Configure the association between the device and the page
     * @param string $device_id Device ID, if UUID, major, minor are filled in, the device ID can be omitted, if both are filled, the device ID will take precedence
     * @param array $page_ids List of pages to be associated
     * @param int $bind association operation flag bit, 0 is to disassociate relationship, 1 is to establish association relationship
     * @param int $append Add operation flag, 0 is overwrite, 1 is new
     * @param string $uuid UUID, major, minor, the three information must be filled in completely, if the device number is filled in, this information can be omitted
     * @param int $major
     * @param int $minor
     * @return bool|array
     */
    public function bindPageShakeAroundDevice($device_id, $page_ids = array(), $bind = 1, $append = 1, $uuid = '', $major = 0, $minor = 0) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        if (!$device_id) {
            if (!$uuid || !$major || !$minor) {
                return false;
            }
            $device_identifier = array('uuid' => $uuid, 'major' => $major, 'minor' => $minor);
        } else {
            $device_identifier = array('device_id' => $device_id);
        }
        $data = array('device_identifier' => $device_identifier, 'page_ids' => $page_ids, 'bind' => $bind, 'append' => $append);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_DEVICE_BINDPAGE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Upload the picture material displayed on the shake page
     * @param array $data {"media":'@Path\filename.jpg'}
     * @return bool|array
     */
    public function uploadShakeAroundMedia($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_MATERIAL_ADD . "access_token={$this->access_token}", $data, true);
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
     * Add the page information that is shaken
     * @param string $title The main title displayed on the shake page, no more than 6 characters
     * @param string $description The subtitle displayed on the shake page, no more than 7 characters
     * @param string $icon_url The image displayed on the shake page, the format is limited to: jpg, jpeg, png, gif; recommended 120*120, the limit does not exceed 200*200
     * @param string $page_url Jump link
     * @param string $comment page remark information, no more than 15 characters, optional
     * @return bool|array
     */
    public function addShakeAroundPage($title, $description, $icon_url, $page_url, $comment = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array("title" => $title, "description" => $description, "icon_url" => $icon_url, "page_url" => $page_url, "comment" => $comment);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_PAGE_ADD . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Edit the page information that is shaken
     * @param int $page_id
     * @param string $title The main title displayed on the shake page, no more than 6 characters
     * @param string $description The subtitle displayed on the shake page, no more than 7 characters
     * @param string $icon_url The image displayed on the shake page, the format is limited to: jpg, jpeg, png, gif; recommended 120*120, the limit does not exceed 200*200
     * @param string $page_url Jump link
     * @param string $comment page remark information, no more than 15 characters, optional
     * @return bool|array
     */
    public function updateShakeAroundPage($page_id, $title, $description, $icon_url, $page_url, $comment = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array("page_id" => $page_id, "title" => $title, "description" => $description, "icon_url" => $icon_url, "page_url" => $page_url, "comment" => $comment);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_PAGE_UPDATE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Query existing pages
     * @param array $page_ids
     * @param int $begin
     * @param int $count
     * @return bool|mixed
     */
    public function searchShakeAroundPage($page_ids = array(), $begin = 0, $count = 1) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        if (!empty($page_ids)) {
            $data = array('page_ids' => $page_ids);
        } else {
            $data = array('begin' => $begin, 'count' => $count);
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_PAGE_SEARCH . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * delete existing page
     * @param array $page_ids
     * @return bool|array
     */
    public function deleteShakeAroundPage($page_ids = array()) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('page_ids' => $page_ids);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_PAGE_DELETE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Get device information
     * @param string $ticket Shake the ticket of the surrounding business (can be obtained from the URL that is shaken, the ticket valid time is 30 minutes)
     * @return bool|array
     */
    public function getShakeInfoShakeAroundUser($ticket) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('ticket' => $ticket);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_USER_GETSHAKEINFO . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Data statistics interface with equipment as the dimension
     * @param int $device_id Device ID, if you fill in UUID, major, minor, you can leave the device ID blank, choose one of the two
     * @param int $begin_date start date timestamp, the longest time span is 30 days
     * @param int $end_date end date timestamp, the maximum time span is 30 days
     * @param string $uuid UUID, major, minor, three pieces of information need to be filled in, if you fill in device editing, you can leave this information blank, choose one of the two
     * @param int $major
     * @param int $minor
     * @return bool|array
     */
    public function deviceShakeAroundStatistics($device_id, $begin_date, $end_date, $uuid = '', $major = 0, $minor = 0) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        if (!$device_id) {
            if (!$uuid || !$major || !$minor) {
                return false;
            }
            $device_identifier = array('uuid' => $uuid, 'major' => $major, 'minor' => $minor);
        } else {
            $device_identifier = array('device_id' => $device_id);
        }
        $data = array('device_identifier' => $device_identifier, 'begin_date' => $begin_date, 'end_date' => $end_date);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_STATISTICS_DEVICE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Data statistics interface with page as dimension
     * @param int $page_id specifies the ID of the page
     * @param int $begin_date start date timestamp, the longest time span is 30 days
     * @param int $end_date end date timestamp, the maximum time span is 30 days
     * @return bool|array
     */
    public function pageShakeAroundStatistics($page_id, $begin_date, $end_date) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('page_id' => $page_id, 'begin_date' => $begin_date, 'end_date' => $end_date);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::SHAKEAROUND_STATISTICS_DEVICE . "access_token={$this->access_token}", Tools::json_encode($data));
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
