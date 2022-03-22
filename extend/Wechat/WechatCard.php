<?php

namespace Wechat;

use Wechat\Lib\Common;
use Wechat\Lib\Tools;

/**
* WeChat card roll
 */
class WechatCard extends Common {

    /** Card and coupon related address */
    const CARD_CREATE = '/card/create?';
    // delete the card volume
    const CARD_DELETE = '/card/delete?';
    // Update card volume information
    const CARD_UPDATE = '/card/update?';
    // Get the card roll details
    const CARD_GET = '/card/get?';
    // Read the list of card rolls owned by fans
    const CARD_USER_GET_LIST = '/card/user/getcardlist?';
    // Card volume verification interface
    const CARD_CHECKCODE = '/card/code/checkcode?';
    // Get HTML by mass sending pictures and texts of card rolls
    const CARD_SET_SELFCONSUMECELL = '/card/selfconsumecell/set?';
    const CARD_SEND_HTML = '/card/mpnews/gethtml?';
    const CARD_BATCHGET = '/card/batchget?';
    const CARD_MODIFY_STOCK = '/card/modifystock?';
    const CARD_GETCOLORS = '/card/getcolors?';
    const CARD_QRCODE_CREATE = '/card/qrcode/create?';
    const CARD_CODE_CONSUME = '/card/code/consume?';
    const CARD_CODE_DECRYPT = '/card/code/decrypt?';
    const CARD_CODE_GET = '/card/code/get?';
    const CARD_CODE_UPDATE = '/card/code/update?';
    const CARD_CODE_UNAVAILABLE = '/card/code/unavailable?';
    const CARD_TESTWHILELIST_SET = '/card/testwhitelist/set?';
    const CARD_MEETINGCARD_UPDATEUSER = '/card/meetingticket/updateuser?'; //Update the meeting ticket
    const CARD_MEMBERCARD_ACTIVATE = '/card/membercard/activate?'; //Activate the membership card
    const CARD_MEMBERCARD_UPDATEUSER = '/card/membercard/updateuser?'; //Update membership card
    const CARD_MOVIETICKET_UPDATEUSER = '/card/movieticket/updateuser?'; //Update movie tickets (no method added)
    const CARD_BOARDINGPASS_CHECKIN = '/card/boardingpass/checkin?'; //Airline ticket - online seat selection (no method added)
    /** Update the red envelope amount */
    const CARD_LUCKYMONEY_UPDATE = '/card/luckymoney/updateuserbalance?';
    /*Buy order interface*/
    const CARD_PAYCELL_SET = '/card/paycell/set?';
    /*Set the card open field interface*/
    const CARD_MEMBERCARD_ACTIVATEUSERFORM_SET = '/card/membercard/activateuserform/set?';

    /**
     * Get WeChat coupon api_ticket
     * @param string $appid
     * @param string $jsapi_ticket
     * @return bool|string
     */
    public function getJsCardTicket($appid = '', $jsapi_ticket = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $appid = empty($appid) ? $this->appid : $appid;
        if ($jsapi_ticket) {
            return $jsapi_ticket;
        }
        $authname = 'wechat_jsapi_ticket_wxcard_' . $appid;
        if (($jsapi_ticket = Tools::getCache($authname))) {
            return $jsapi_ticket;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::GET_TICKET_URL . "access_token={$this->access_token}" . '&type=wx_card');
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            $expire = $json['expires_in'] ? intval($json['expires_in']) - 100 : 3600;
            Tools::setCache($authname, $json['ticket'], $expire);
            return $json['ticket'];
        }
        return false;
    }

    /**
    * Generate selected card roll JS signature package
     * @param string $cardid card ID
     * @param string $cardtype card type
     * @param string $shopid shop id
     * @return array
     */
    public function createChooseCardJsPackage($cardid = NULL, $cardtype = NULL, $shopid = NULL) {
        $data = array();
        $data['api_ticket'] = $this->getJsCardTicket();
        $data['app_id'] = $this->appid;
        $data['timestamp'] = time();
        $data['nonceStr'] = Tools::createNoncestr();
        !empty($cardid) && $data['cardId'] = $cardid;
        !empty($cardtype) && $data['cardType'] = $cardtype;
        !empty($shopid) && $data['shopId'] = $shopid;
        $data['cardSign'] = $this->getTicketSignature($data);
        $data['signType'] = 'SHA1';
        unset($data['api_ticket'], $data['app_id']);
        return $data;
    }

    /**
     * Generate add card volume JS signature package
     * @param string|null $cardid card volume ID
     * @param array $data other qualified parameters
     * @return array
     */
    public function createAddCardJsPackage($cardid = NULL, $data = array()) {

        function _sign($cardid = NULL, $attr = array(), $self) {
            unset($attr['outer_id']);
            $attr['cardId'] = $cardid;
            $attr['timestamp'] = time();
            $attr['api_ticket'] = $self->getJsCardTicket();
            $attr['nonce_str'] = Tools::createNoncestr();
            $attr['signature'] = $self->getTicketSignature($attr);
            unset($attr['api_ticket']);
            return $attr;
        }

        $cardList = array();
        if (is_array($cardid)) {
            foreach ($cardid as $id) {
                $cardList[] = array('cardId' => $id, 'cardExt' => json_encode(_sign($id, $data, $this)));
            }
        } else {
            $cardList[] = array('cardId' => $cardid, 'cardExt' => json_encode(_sign($cardid, $data, $this)));
        }
        return array('cardList' => $cardList);
    }

    /**
     * Get WeChat card coupon signature
     * @param array $arrdata signature array
     * @param string $method signature method
     * @return bool|string signature value
     */
    public function getTicketSignature($arrdata, $method = "sha1") {
        if (!function_exists($method)) {
            return false;
        }
        $newArray = array();
        foreach ($arrdata as $value) {
            array_push($newArray, (string)$value);
        }
        sort($newArray, SORT_STRING);
        return $method(implode($newArray));
    }

    /**
     * Create coupons
     * @param array $data card data
     * @return bool|array The card_id in the returned array is the card ID
     */
    public function createCard($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_CREATE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Change the card information
     * After calling this interface to update the information, it will be re-submitted for review, and the status of the coupons will be changed to pending review. The coupon information that has been claimed by the user will be updated in real time.
     * @param string $data
     * @return bool
     */
    public function updateCard($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_UPDATE . "access_token={$this->access_token}", Tools::json_encode($data));
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
    * delete coupons
     * Allows merchants to delete any type of coupons. After the card coupon is deleted, the generated QR code for the card coupon and the JS API added to the card package will be invalid.
     * Note: Deleting cards and coupons cannot delete the cards and coupons that have been claimed by the user and saved in the WeChat client. The cards and coupons that have been claimed are still valid.
     * @param string $card_id Card ID
     * @return bool
     */
    public function delCard($card_id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('card_id' => $card_id);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_DELETE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Get a list of all card rolls under fans
     * @param $openid fan openid
     * @param string $card_id card roll ID (optional)
     * @return bool|array
     */
    public function getCardList($openid, $card_id = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('openid' => $openid);
        !empty($card_id) && $data['card_id'] = $card_id;
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_USER_GET_LIST . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode']) || empty($json['card_list'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Get the HTML of mass card coupons issued by graphic message
     * @param string $card_id card roll ID
     * @return bool|array
     */
    public function getCardMpHtml($card_id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('card_id' => $card_id);
        !empty($card_id) && $data['card_id'] = $card_id;
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_SEND_HTML . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode']) || empty($json['card_list'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Card roll code verification
     * @param string $card_id card roll ID
     * @param array $code_list card roll code list (one-dimensional array)
     * @return bool|array
     */
    public function checkCardCodeList($card_id, $code_list) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('card_id' => $card_id, 'code' => $code_list);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_CHECKCODE . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode']) || empty($json['card_list'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
    * Check card details
     * @param string $card_id card roll ID
     * @return bool|array
     */
    public function getCardInfo($card_id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('card_id' => $card_id);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_GET . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Get a list of colors
     * Get the latest color list of coupons for creating coupons
     * @return bool|array
     */
    public function getCardColors() {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_BASE_URL_PREFIX . self::CARD_GETCOLORS . "access_token={$this->access_token}");
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
     * Generate QR code for card and coupon
     * If successful, return the ticket value directly, you can use getQRUrl($ticket) to exchange for the QR code url
     * @param string $card_id Card ID Required
     * @param string $code specifies the code of the card and coupon, which can only be claimed once. Cards and coupons whose use_custom_code field is true must be filled in, and non-custom code is not required.
     * @param string $openid Specifies the openid of the recipient, only the user can claim it. Cards and coupons whose bind_openid field is true must be filled in, and non-custom openid is not required.
     * @param int $expire_seconds Specify the valid time of the QR code, the range is 60 ~ 1800 seconds. If not filled, the default is permanent.
     * @param bool $is_unique_code Specifies the QR code to be issued. The generated QR code is randomly assigned a code, which cannot be scanned again after receiving it. Fill in true or false. Default false.
     * @param string $balance Red envelope balance, in cents. The red packet type is required (LUCKY_MONEY), and other card coupon types are not required.
     * @return bool|string
     */
    public function createCardQrcode($card_id, $code = '', $openid = '', $expire_seconds = 0, $is_unique_code = false, $balance = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $card = array('card_id' => $card_id);
        !empty($code) && $card['code'] = $code;
        !empty($openid) && $card['openid'] = $openid;
        !empty($is_unique_code) && $card['is_unique_code'] = $is_unique_code;
        !empty($balance) && $card['balance'] = $balance;
        $data = array('action_name' => "QR_CARD");
        !empty($expire_seconds) && $data['expire_seconds'] = $expire_seconds;
        $data['action_info'] = array('card' => $card);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_QRCODE_CREATE . "access_token={$this->access_token}", Tools::json_encode($data));
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
    * consume code
     * For coupons with custom code (use_custom_code is true), this interface must be called when the code is written off.
     * @param string $code The serial number to consume
     * @param string $card_id To consume the card_id described in the serial number, it is required when use_custom_code is true when creating a card and coupon.
     * @return bool|array
     * {
     *  "errcode":0,
     *  "errmsg":"ok",
     *  "card":{"card_id":"pFS7Fjg8kV1IdDz01r4SQwMkuCKc"},
     *  "openid":"oFS7Fjl0WsZ9AMZqrI80nbIq8xrA"
     * }
     */
    public function consumeCardCode($code, $card_id = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('code' => $code);
        !empty($card_id) && $data['card_id'] = $card_id;
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_CODE_CONSUME . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * code decoding
     * @param string $encrypt_code The encrypted string obtained by choose_card_info
     * @return bool|array
     * {
     *  "errcode":0,
     *  "errmsg":"ok",
     *  "code":"751234212312"
     *  }
     */
    public function decryptCardCode($encrypt_code) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('encrypt_code' => $encrypt_code,);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_CODE_DECRYPT . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Query the validity of the code (non-custom code)
     * @param string $code
     * @return bool|array
     * {
     * "errcode":0,
     * "errmsg": "ok",
     * "openid":"oFS7Fjl0WsZ9AMZqrI80nbIq8xrA", //User openid
     * "card":{
     * "card_id":"pFS7Fjg8kV1IdDz01r4SQwMkuCKc",
     * "begin_time": 1404205036, //Start usage time
     * "end_time": 1404205036, //end time
     *  }
     * }
     */
    public function checkCardCode($code) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('code' => $code);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_CODE_GET . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Bulk query card list
     * @param int $offset The offset to start pulling, the default is 0 to start from the beginning
     * @param int $count The number of cards to be queried (the maximum number is 50, the default is 50)
     * @return bool|array
     * {
     * "errcode":0,
     * "errmsg": "ok",
     * "card_id_list":["ph_gmt7cUVrlRk8swPwx7aDyF-pg"], //card id list
     * "total_num":1 //Total number of card_id under the merchant's name
     * }
     */
    public function getCardIdList($offset = 0, $count = 50) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $count > 50 && $count = 50;
        $data = array('offset' => $offset, 'count' => $count);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_BATCHGET . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * change code
     * In order to ensure the security of the gift, WeChat allows merchants who customize the code to change the code that has been issued.
     * Note: In order to avoid user confusion, it is recommended to change the user's code only after the transfer (after the transfer, WeChat will notify the merchant of the code of the card and coupon that was transferred by means of event push).
     * @param string $code The code code of the coupon
     * @param string $card_id Card ID
     * @param string $new_code New card code code
     * @return bool
     */
    public function updateCardCode($code, $card_id, $new_code) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('code' => $code, 'card_id' => $card_id, 'new_code' => $new_code);
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_CODE_UPDATE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Set coupons are invalid
     * The operation of setting the invalidation of cards and coupons is irreversible
     * @param string $code needs to be set to invalid code
     * @param string $card_id Required for custom code. Cards and coupons with non-custom code are not filled.
     * @return bool
     */
    public function unavailableCardCode($code, $card_id = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('code' => $code);
        !empty($card_id) && $data['card_id'] = $card_id;
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_CODE_UNAVAILABLE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * stock modification
     * @param string $data
     * @return bool
     */
    public function modifyCardStock($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_MODIFY_STOCK . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Update tickets
     * @param string $data
     * @return bool
     */
    public function updateMeetingCard($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_MEETINGCARD_UPDATEUSER . "access_token={$this->access_token}", Tools::json_encode($data));
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
    * Activate/bind membership card
     * @param string $data For the specific structure, please refer to the chapter of the card and coupon development document (6.1.1 Activating/Binding Membership Card)
     * @return bool
     */
    public function activateMemberCard($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_MEMBERCARD_ACTIVATE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Membership card transaction
     * After the membership card transaction, every change of points and balance needs to be notified through the interface of WeChat, which is convenient for follow-up message notification and other extended functions.
     * @param string $data For the specific structure, please refer to the chapter of the card and coupon development document (6.1.2 Membership card transaction)
     * @return bool|array
     */
    public function updateMemberCard($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_MEMBERCARD_UPDATEUSER . "access_token={$this->access_token}", Tools::json_encode($data));
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
    * Set the card test whitelist
     * @param array $openid list of openids to test
     * @param array $user list of micro-signals to test
     * @return bool
     */
    public function setCardTestWhiteList($openid = array(), $user = array()) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array();
        count($openid) > 0 && $data['openid'] = $openid;
        count($user) > 0 && $data['username'] = $user;
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_TESTWHILELIST_SET . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Update the red envelope amount
     * @param string $code The serial number of the red packet
     * @param int $balance red packet balance
     * @param string $card_id Required for custom code. Non-custom code can be left blank.
     * @return bool|array
     */
    public function updateLuckyMoney($code, $balance, $card_id = '') {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('code' => $code, 'balance' => $balance);
        !empty($card_id) && $data['card_id'] = $card_id;
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_LUCKYMONEY_UPDATE . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Set up self-service write-off interface
     * @param string $card_id Card ID
     * @param bool $is_openid Whether to enable the self-service verification function, fill in true/false, the default is false
     * @param bool $need_verify_cod Whether the user needs to enter the verification code when writing off, fill in true/false, the default is false
     * @param bool $need_remark_amount Whether the user needs to remark the write-off amount when writing off, fill in true/false, the default is false
     * @return bool|array
     */
    public function setSelfconsumecell($card_id, $is_openid = false, $need_verify_cod = false, $need_remark_amount = false) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array(
            'card_id'            => $card_id,
            'is_open'            => $is_openid,
            'need_verify_cod'    => $need_verify_cod,
            'need_remark_amount' => $need_remark_amount,
        );
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_SET_SELFCONSUMECELL . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Set up buy order interface
     * @param string $card_id
     * @param bool $is_openid
     * @return bool|mixed
     */
    public function setPaycell($card_id, $is_openid = true) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array(
            'card_id' => $card_id,
            'is_open' => $is_openid,
        );
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_PAYCELL_SET . "access_token={$this->access_token}", Tools::json_encode($data));
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
     * Set the card opening field information interface
     * @param array $data
     * @return bool|array
     */
    public function setMembercardActivateuserform($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_BASE_URL_PREFIX . self::CARD_MEMBERCARD_ACTIVATEUSERFORM_SET . "access_token={$this->access_token}", Tools::json_encode($data));
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
