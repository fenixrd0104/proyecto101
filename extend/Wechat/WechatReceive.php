<?php

namespace Wechat;

use Prpcrypt;
use Wechat\Lib\Common;
use Wechat\Lib\Tools;

/**
* WeChat message object parsing SDK
 *
 * @author Anyon <zoujingli@qq.com>
 * @date 2016/06/28 11:29
 */
class WechatReceive extends Common {

    /** Message push address */
    const CUSTOM_SEND_URL = '/message/custom/send?';
    const MASS_SEND_URL = '/message/mass/send?';
    const TEMPLATE_SET_INDUSTRY_URL = '/message/template/api_set_industry?';
    const TEMPLATE_ADD_TPL_URL = '/message/template/api_add_template?';
    const TEMPLATE_SEND_URL = '/message/template/send?';
    const MASS_SEND_GROUP_URL = '/message/mass/sendall?';
    const MASS_DELETE_URL = '/message/mass/delete?';
    const MASS_PREVIEW_URL = '/message/mass/preview?';
    const MASS_QUERY_URL = '/message/mass/get?';

    /** message reply type */
    const MSGTYPE_TEXT = 'text';
    const MSGTYPE_IMAGE = 'image';
    const MSGTYPE_LOCATION = 'location';
    const MSGTYPE_LINK = 'link';
    const MSGTYPE_EVENT = 'event';
    const MSGTYPE_MUSIC = 'music';
    const MSGTYPE_NEWS = 'news';
    const MSGTYPE_VOICE = 'voice';
    const MSGTYPE_VIDEO = 'video';

/** file filter */
    protected $_text_filter = true;

    /** message object */
    private $_receive;

    /**
     * Get the content sent by the WeChat server
     * @return $this
     */
    public function getRev() {
        if ($this->_receive) {
            return $this;
        }
        $postStr = !empty($this->postxml) ? $this->postxml : file_get_contents("php://input");
        !empty($postStr) && $this->_receive = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $this;
    }

    /**
     * Get the information data sent by the WeChat server
     * @return array
     */
    public function getRevData() {
        return $this->_receive;
    }

    /**
    * Get message sender
     * @return bool|string
     */
    public function getRevFrom() {
        if (isset($this->_receive['FromUserName'])) {
            return $this->_receive['FromUserName'];
        }
        return false;
    }

    /**
     * Get message recipient
     * @return bool|string
     */
    public function getRevTo() {
        if (isset($this->_receive['ToUserName'])) {
            return $this->_receive['ToUserName'];
        }
        return false;
    }

    /**
     * Get the type of received message
     * @return bool|string
     */
    public function getRevType() {
        if (isset($this->_receive['MsgType'])) {
            return $this->_receive['MsgType'];
        }
        return false;
    }

    /**
     * Get message ID
     * @return bool|string
     */
    public function getRevID() {
        if (isset($this->_receive['MsgId'])) {
            return $this->_receive['MsgId'];
        }
        return false;
    }

    /**
     * Get message sending time
     * @return bool|string
     */
    public function getRevCtime() {
        if (isset($this->_receive['CreateTime'])) {
            return $this->_receive['CreateTime'];
        }
        return false;
    }

    /**
     * Get the card and coupon event push - whether the card roll audit passed
     * When the Event is card_pass_check (approved) or card_not_pass_check (failed)
     * @return bool|string Returns the card ID
     */
    public function getRevCardPass() {
        if (isset($this->_receive['CardId'])) {
            return $this->_receive['CardId'];
        }
        return false;
    }

    /**
     * Get card and coupon event push - get card and coupon
     * When the Event is user_get_card (the user gets the card and coupon)
     * @return bool|array
     */
    public function getRevCardGet() {
        $array = array();
        if (isset($this->_receive['CardId'])) {
            $array['CardId'] = $this->_receive['CardId'];
        }
        if (isset($this->_receive['IsGiveByFriend'])) {
            $array['IsGiveByFriend'] = $this->_receive['IsGiveByFriend'];
        }
        $array['OldUserCardCode'] = $this->_receive['OldUserCardCode'];
        if (isset($this->_receive['UserCardCode']) && !empty($this->_receive['UserCardCode'])) {
            $array['UserCardCode'] = $this->_receive['UserCardCode'];
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        }
        return false;
    }

    /**
     * Get card coupon event push - delete card coupon
     * When the Event is user_del_card (the user deletes the card)
     * @return bool|array
     */
    public function getRevCardDel() {
        if (isset($this->_receive['CardId'])) { //Card ID
            $array['CardId'] = $this->_receive['CardId'];
        }
        if (isset($this->_receive['UserCardCode']) && !empty($this->_receive['UserCardCode'])) {
            $array['UserCardCode'] = $this->_receive['UserCardCode'];
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        }
        return false;
    }

    /**
     * Get the body of the received message content
     * @return bool
     */
    public function getRevContent() {
        if (isset($this->_receive['Content'])) {
            return $this->_receive['Content'];
        } else if (isset($this->_receive['Recognition'])) { //To get the text content of speech recognition, you need to apply for activation
            return $this->_receive['Recognition'];
        }
        return false;
    }

    /**
     * Get the picture of the received message
     * @return array|bool
     */
    public function getRevPic() {
        if (isset($this->_receive['PicUrl'])) {
            return array(
                'mediaid' => $this->_receive['MediaId'],
                'picurl' => (string)$this->_receive['PicUrl'], //Prevent parsing errors due to empty picurl
            );
        }
        return false;
    }

    /**
     * Get the link to receive messages
     * @return bool|array
     */
    public function getRevLink() {
        if (isset($this->_receive['Url'])) {
            return array(
                'url'         => $this->_receive['Url'],
                'title'       => $this->_receive['Title'],
                'description' => $this->_receive['Description']
            );
        }
        return false;
    }

    /**
     * Get receiving geographic location
     * @return bool|array
     */
    public function getRevGeo() {
        if (isset($this->_receive['Location_X'])) {
            return array(
                'x'     => $this->_receive['Location_X'],
                'y'     => $this->_receive['Location_Y'],
                'scale' => $this->_receive['Scale'],
                'label' => $this->_receive['Label']
            );
        }
        return false;
    }

    /**
     * Get and report geolocation events
     * @return bool|array
     */
    public function getRevEventGeo() {
        if (isset($this->_receive['Latitude'])) {
            return array(
                'x'         => $this->_receive['Latitude'],
                'y'         => $this->_receive['Longitude'],
                'precision' => $this->_receive['Precision'],
            );
        }
        return false;
    }

    /**
     * Get receive event push
     * @return bool|array
     */
    public function getRevEvent() {
        if (isset($this->_receive['Event'])) {
            $array['event'] = $this->_receive['Event'];
        }
        if (isset($this->_receive['EventKey'])) {
            $array['key'] = $this->_receive['EventKey'];
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        }
        return false;
    }

    /**
    * Get the scan code push event information of the custom menu
     *
     * Calling this method is valid when the event type is the following two
     * Event event type, scancode_push
     * Event event type, scancode_waitmsg
     * @return bool|array
     */
    public function getRevScanInfo() {
        if (isset($this->_receive['ScanCodeInfo'])) {
            if (!is_array($this->_receive['ScanCodeInfo'])) {
                $array = (array)$this->_receive['ScanCodeInfo'];
                $this->_receive['ScanCodeInfo'] = $array;
            } else {
                $array = $this->_receive['ScanCodeInfo'];
            }
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        }
        return false;
    }

    /**
     * Get the picture sending event information of the custom menu
     *
     * Calling this method is valid when the event type is the following three
     * Event event type, pic_sysphoto pops up the event push for the system to take pictures and send pictures
     * Event event type, pic_photo_or_album pops up the event push of taking photos or photo albums
     * Event event type, pic_weixin pops up the event push of WeChat album imager
     *
     * @return bool|array
     * array (
     *   'Count' => '2',
     *   'PicList' =>array (
     *         'item' =>array (
     *             0 =>array ('PicMd5Sum' => 'aaae42617cf2a14342d96005af53624c'),
     *             1 =>array ('PicMd5Sum' => '149bd39e296860a2adc2f1bb81616ff8'),
     *         ),
     *   ),
     * )
     *
     */
    public function getRevSendPicsInfo() {
        if (isset($this->_receive['SendPicsInfo'])) {
            if (!is_array($this->_receive['SendPicsInfo'])) {
                $array = (array)$this->_receive['SendPicsInfo'];
                if (isset($array['PicList'])) {
                    $array['PicList'] = (array)$array['PicList'];
                    $item = $array['PicList']['item'];
                    $array['PicList']['item'] = array();
                    foreach ($item as $key => $value) {
                        $array['PicList']['item'][$key] = (array)$value;
                    }
                }
                $this->_receive['SendPicsInfo'] = $array;
            } else {
                $array = $this->_receive['SendPicsInfo'];
            }
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        }
        return false;
    }

    /**
    * Get geo-selector event push for custom menu
     *
     * This method can be called when the event type is the following
     * Event event type, location_select pops up the event push of the geographic location selector
     *
     * @return bool|array
     * array (
     * 'Location_X' => '33.731655000061',
     * 'Location_Y' => '113.29955200008047',
     * 'Scale' => '16',
     * 'Label' => 'A certain road in a certain district of a certain city',
     *   'Poiname' => '',
     * )
     *
     */
    public function getRevSendGeoInfo() {
        if (isset($this->_receive['SendLocationInfo'])) {
            if (!is_array($this->_receive['SendLocationInfo'])) {
                $array = (array)$this->_receive['SendLocationInfo'];
                if (empty($array['Poiname'])) {
                    $array['Poiname'] = "";
                }
                if (empty($array['Label'])) {
                    $array['Label'] = "";
                }
                $this->_receive['SendLocationInfo'] = $array;
            } else {
                $array = $this->_receive['SendLocationInfo'];
            }
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        }
        return false;
    }

    /**
     * Get and receive voice push
     * @return bool|array
     */
    public function getRevVoice() {
        if (isset($this->_receive['MediaId'])) {
            return array(
                'mediaid' => $this->_receive['MediaId'],
                'format'  => $this->_receive['Format'],
            );
        }
        return false;
    }

    /**
     * Get and receive video push
     * @return array|bool
     */
    public function getRevVideo() {
        if (isset($this->_receive['MediaId'])) {
            return array(
                'mediaid'      => $this->_receive['MediaId'],
                'thumbmediaid' => $this->_receive['ThumbMediaId']
            );
        }
        return false;
    }

    /**
     * Get receive TICKET
     * @return bool|string
     */
    public function getRevTicket() {
        if (isset($this->_receive['Ticket'])) {
            return $this->_receive['Ticket'];
        }
        return false;
    }

    /**
     * Get the scene value of the QR code
     * @return bool|string
     */
    public function getRevSceneId() {
        if (isset($this->_receive['EventKey'])) {
            return str_replace('qrscene_', '', $this->_receive['EventKey']);
        }
        return false;
    }

    /**
     * Get the message ID of the active push
     * After verification, this is not the same as the ordinary message MsgId
     * When Event is MASSSENDJOBFINISH or TEMPLATESENDJOBFINISH
     * @return bool|string
     */
    public function getRevTplMsgID() {
        if (isset($this->_receive['MsgID'])) {
            return $this->_receive['MsgID'];
        }
        return false;
    }

    /**
     * Get template message sending status
     * @return bool|string
     */
    public function getRevStatus() {
        if (isset($this->_receive['Status'])) {
            return $this->_receive['Status'];
        }
        return false;
    }

    /**
     * Get group or template message sending results
     * When Event is MASSSENDJOBFINISH or TEMPLATESENDJOBFINISH, ie advanced mass/template message
     * @return bool|array
     */
    public function getRevResult() {
        if (isset($this->_receive['Status'])) { //Whether the sending is successful, please refer to the event push description of Advanced Mass Sending/Template Message for the specific return value
            $array['Status'] = $this->_receive['Status'];
        }
        if (isset($this->_receive['MsgID'])) { //Sent message id
            $array['MsgID'] = $this->_receive['MsgID'];
        }
        //The following event content will only be available when group messages are sent
        if (isset($this->_receive['TotalCount'])) { //Number of fans in group or openid list
            $array['TotalCount'] = $this->_receive['TotalCount'];
        }
        if (isset($this->_receive['FilterCount'])) { //Filtering (filtering refers to the filtering of specific regions, genders, the filtering that the user sets to reject, the user receives more than 4 filters), prepare number of followers sent
            $array['FilterCount'] = $this->_receive['FilterCount'];
        }
        if (isset($this->_receive['SentCount'])) { //Number of followers sent successfully
            $array['SentCount'] = $this->_receive['SentCount'];
        }
        if (isset($this->_receive['ErrorCount'])) { //Number of followers who failed to send
            $array['ErrorCount'] = $this->_receive['ErrorCount'];
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        }
        return false;
    }

    /**
     * Get multiple customer service session status push events - access session
     * When the Event is kfcreatesession, the session is accessed
     * @return bool|string
     */
    public function getRevKFCreate() {
        if (isset($this->_receive['KfAccount'])) {
            return $this->_receive['KfAccount'];
        }
        return false;
    }

    /**
     * Get multiple customer service session status push events - close the session
     * When the Event is kfclosesession, the session is closed
     * @return bool|string
     */
    public function getRevKFClose() {
        if (isset($this->_receive['KfAccount'])) {
            return $this->_receive['KfAccount'];
        }
        return false;
    }

    /**
     * Get multiple customer service session status push events - transfer session
     * When the Event is kfswitchsession, the session is transferred
     * @return bool|array
     */
    public function getRevKFSwitch() {
        if (isset($this->_receive['FromKfAccount'])) { //Original access customer service
            $array['FromKfAccount'] = $this->_receive['FromKfAccount'];
        }
        if (isset($this->_receive['ToKfAccount'])) { //Transfer to customer service
            $array['ToKfAccount'] = $this->_receive['ToKfAccount'];
        }
        if (isset($array) && count($array) > 0) {
            return $array;
        }
        return false;
    }

    /**
     * Send customer service message
     * @param array $data message structure {"touser":"OPENID","msgtype":"news","news":{...}}
     * @return bool|array
     */
    public function sendCustomMessage($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::CUSTOM_SEND_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Template message Set industry
     * @param string $id1 The industry ID of the official account template message, please refer to the official development document Industry code
     * @param string $id2 same as $id1. But if there is only one industry, this parameter can be omitted
     * @return bool|mixed
     */
    public function setTMIndustry($id1, $id2 = '') {
        if ($id1) {
            $data['industry_id1'] = $id1;
        }
        if ($id2) {
            $data['industry_id2'] = $id2;
        }
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::TEMPLATE_SET_INDUSTRY_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Template message Add message template
     * Successfully returns the call id of the message template
     * @param string $tpl_id The template number in the template library, in the form of "TM**" and "OPENTMTM**"
     * @return bool|string
     */
    public function addTemplateMessage($tpl_id) {
        $data = array('template_id_short' => $tpl_id);
        if (!$this->access_token && !$this->getAccessToken())
            return false;
        $result = Tools::httpPost(self::API_URL_PREFIX . self::TEMPLATE_ADD_TPL_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json['template_id'];
        }
        return false;
    }

    /**
     * Send template message
     * @param array $data message structure
     * {
     *      "touser":"OPENID",
     *       "template_id":"ngqIpbwh8bUfcSsECmogfXcV14J0tQlEpBO27izEYtY",
     *       "url":"http://weixin.qq.com/download",
     *       "topcolor":"#FF0000",
     *       "data":{
     *          "parameter name 1": {
     * 			"value":"parameter",
     * 			"color":"#173177" //Parameter color
     * 			},
     * 			"Date":{
     * 			"value":"June 7th 19:24",
     * 			"color":"#173177"
     * 			},
     * 			"CardNumber":{
     * 			"value":"0426",
     * 			"color":"#173177"
     * 			},
     * 			"Type":{
     * 			"value":"Consumption",
     *          "color":"#173177"
     *       }
     *   }
     * }
     * @return bool|array
     */
    public function sendTemplateMessage($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::TEMPLATE_SEND_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Forward multiple customer service messages
     * @param string $customer_account
     * @return $this
     */
    public function transfer_customer_service($customer_account = '') {
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'CreateTime'   => time(),
            'MsgType'      => 'transfer_customer_service',
        );
        if ($customer_account) {
            $msg['TransInfo'] = array('KfAccount' => $customer_account);
        }
        $this->Message($msg);
        return $this;
    }

    /**
     * Advanced mass messaging, mass messaging according to the OpenID list (subscription number is not available)
     * Note: The video needs to be generated using the uploadMpVideo() method after calling the uploadMedia() method.
     * Then the obtained mediaid can be used for mass sending, and the message type is mpvideo type.
     * @param array $data message structure
     * {
     * "touser"=>array(
     * "OPENID1",
     * "OPENID2"
     * ),
     * "msgtype"=>"mpvideo",
     * // Select the corresponding parameter content from the following 5 types
     *      // mpnews | voice | image | mpvideo => array( "media_id"=>"MediaId")
     *      // text => array ( "content" => "hello")
     * }
     * @return bool|array
     */
    public function sendMassMessage($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MASS_SEND_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Advanced mass messaging, mass messaging according to group id (authenticated subscription number is available)
     * Note: The video needs to be generated using the uploadMpVideo() method after calling the uploadMedia() method.
     * Then the obtained mediaid can be used for mass sending, and the message type is mpvideo type.
     * @param array $data message structure
     * {
     * "filter"=>array(
     * "is_to_all"=>False, //Whether it is sent to all users. True does not need group id, False needs to fill in group id
     * "group_id"=>"2" //Group id sent by group
     * ),
     * "msgtype"=>"mpvideo",
     * // Select the corresponding parameter content from the following 5 types
     *      // mpnews | voice | image | mpvideo => array( "media_id"=>"MediaId")
     *      // text => array ( "content" => "hello")
     * }
     * @return bool|array
     */
    public function sendGroupMassMessage($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MASS_SEND_GROUP_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Advanced mass messaging, delete mass messaging (authenticated subscription numbers are available)
     * @param string $msg_id message ID
     * @return bool
     */
    public function deleteMassMessage($msg_id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MASS_DELETE_URL . "access_token={$this->access_token}", Tools::json_encode(array('msg_id' => $msg_id)));
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
     * Advanced mass messaging, preview mass messaging (authenticated subscription numbers are available)
     * Note: The video needs to be generated using the uploadMpVideo() method after calling the uploadMedia() method.
     * Then the obtained mediaid can be used for mass sending, and the message type is mpvideo type.
     * @param type $data
     * @message structure
     * {
     *     "touser"=>"OPENID",
     *      "msgtype"=>"mpvideo",
     *      // Select the corresponding parameter content from the following 5 types
     *      // mpnews | voice | image | mpvideo => array( "media_id"=>"MediaId")
     *      // text => array ( "content" => "hello")
     * }
     * @return bool|array
     */
    public function previewMassMessage($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MASS_PREVIEW_URL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Advanced mass messaging, query the sending status of mass messaging (authenticated subscription numbers are available)
     * @param string $msg_id message ID
     * @return bool|array
     * {
     * "msg_id":201053012, //The message id returned after mass sending
     * "msg_status":"SEND_SUCCESS" //The status after the message is sent, SENDING means sending SEND_SUCCESS means sending successfully
     * }
     */
    public function queryMassMessage($msg_id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MASS_QUERY_URL . "access_token={$this->access_token}", Tools::json_encode(array('msg_id' => $msg_id)));
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
     * Set send message
     * @param string|array $msg message array
     * @param bool $append whether to append to the original message array
     * @return array
     */
    public function Message($msg = '', $append = false) {
        if (is_null($msg)) {
            $this->_msg = array();
        } elseif (is_array($msg)) {
            if ($append) {
                $this->_msg = array_merge($this->_msg, $msg);
            } else {
                $this->_msg = $msg;
            }
            return $this->_msg;
        }
        return $this->_msg;
    }

    /**
     * Set text message
     * @param string $text text content
     * @return $this
     */
    public function text($text = '') {
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType'      => self::MSGTYPE_TEXT,
            'Content'      => $this->_auto_text_filter($text),
            'CreateTime'   => time(),
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * Set picture message
     * @param string $mediaid Image media ID
     * @return $this
     */
    public function image($mediaid = '') {
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType'      => self::MSGTYPE_IMAGE,
            'Image'        => array('MediaId' => $mediaid),
            'CreateTime'   => time(),
        );
        $this->Message($msg);
        return $this;
    }

    /**
    * Set voice reply message
     * @param string $mediaid Voice media ID
     * @return $this
     */
    public function voice($mediaid = '') {
        $msg = array(
            'ToUserName' => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType' => self::MSGTYPE_VOICE,
            'Voice' => array('MediaId' => $mediaid),
            'CreateTime' => time(),
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * Set video reply message
     * @param string $mediaid Video media ID
     * @param string $title video title
     * @param string $description video description
     * @return $this
     */
    public function video($mediaid = '', $title = '', $description = '') {
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'MsgType'      => self::MSGTYPE_VIDEO,
            'Video'        => array(
                'MediaId'     => $mediaid,
                'Title'       => $title,
                'Description' => $description
            ),
            'CreateTime'   => time(),
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * Set music reply message
     * @param string $title music title
     * @param string $desc music description
     * @param string $musicurl Music URL
     * @param string $hgmusicurl HD music address
     * @param string $thumbmediaid The media id of the music image thumbnail (optional)
     * @return $this
     */
    public function music($title, $desc, $musicurl, $hgmusicurl = '', $thumbmediaid = '') {
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'CreateTime'   => time(),
            'MsgType'      => self::MSGTYPE_MUSIC,
            'Music'        => array(
                'Title'       => $title,
                'Description' => $desc,
                'MusicUrl'    => $musicurl,
                'HQMusicUrl'  => $hgmusicurl
            ),
        );
        if ($thumbmediaid) {
            $msg['Music']['ThumbMediaId'] = $thumbmediaid;
        }
        $this->Message($msg);
        return $this;
    }

    /**
     * Set reply text
     * @param array $newsData
     * @return $this
     */
    public function news($newsData = array()) {
        $msg = array(
            'ToUserName'   => $this->getRevFrom(),
            'FromUserName' => $this->getRevTo(),
            'CreateTime'   => time(),
            'MsgType'      => self::MSGTYPE_NEWS,
            'ArticleCount' => count($newsData),
            'Articles'     => $newsData,
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * Reply to WeChat server
     * @param array $msg The message to be sent ($this->_msg by default)
     * @param bool $return whether to return information without throwing to the browser (default: no)
     * @return bool|string
     */
    public function reply($msg = array(), $return = false) {
        if (empty($msg)) {
            if (empty($this->_msg)) { //Prevent the reply method directly calling the reply method without setting the reply content first, which will cause an exception
                return false;
            }
            $msg = $this->_msg;
        }
        $xmldata = Tools::arr2xml($msg);
        if ($this->encrypt_type == 'aes') { //If the source message is encrypted
            !class_exists('Prpcrypt', FALSE) && require __DIR__ . '/Lib/Prpcrypt.php';
            $pc = new Prpcrypt($this->encodingAesKey);
            // If it is a third-party platform, use component_appid for encryption
            $array = $pc->encrypt($xmldata, empty($this->config['component_appid']) ? $this->appid : $this->config['component_appid']);
            $ret = $array[0];
            if ($ret != 0) {
                Tools::log('encrypt err!');
                return false;
            }
            $timestamp = time();
            $nonce = rand(77, 999) * rand(605, 888) * rand(11, 99);
            $encrypt = $array[1];
            $tmpArr = array($this->token, $timestamp, $nonce, $encrypt); //There is one more encrypted ciphertext than the general public platform
            sort($tmpArr, SORT_STRING);
            $signature = sha1(implode($tmpArr));
            $format = "<xml><Encrypt><![CDATA[%s]]></Encrypt><MsgSignature><![CDATA[%s]]></MsgSignature><TimeStamp>%s</TimeStamp><Nonce><![CDATA[%s]]></Nonce></xml>";
            $xmldata = sprintf($format, $encrypt, $signature, $timestamp, $nonce);
        }
        if ($return) {
            return $xmldata;
        }
        echo $xmldata;
    }

    /**
     * Filter text reply\r\nnewlines
     * @param string $text
     * @return string
     */
    private function _auto_text_filter($text) {
        if (!$this->_text_filter) {
            return $text;
        }
        return str_replace("\r\n", "\n", $text);
    }

}
