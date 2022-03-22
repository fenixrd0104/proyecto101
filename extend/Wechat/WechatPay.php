<?php

namespace Wechat;

use Wechat\Lib\Tools;

/**
* WeChat payment SDK
 * @author zoujingli <zoujingli@qq.com>
 * @date 2015/05/13 12:12:00
 */
class WechatPay {

    /** Payment interface base address */
    const MCH_BASE_URL = 'https://api.mch.weixin.qq.com';

    /** Official account appid */
    public $appid;

    /** Merchant ID */
    public $mch_id;

    /** Merchant payment key Key */
    public $partnerKey;

    /** certificate path */
    public $ssl_cer;
    public $ssl_key;

    /** Execute error message and code */
    public $errMsg;
    public $errCode;

    /**
     * WechatPay constructor.
     * @param array $options
     */
    public function __construct($options = array()) {
        $config = Loader::config($options);
        $this->appid = isset($config['appid']) ? $config['appid'] : '';
        $this->mch_id = isset($config['mch_id']) ? $config['mch_id'] : '';
        $this->partnerKey = isset($config['partnerkey']) ? $config['partnerkey'] : '';
        $this->ssl_cer = isset($config['ssl_cer']) ? $config['ssl_cer'] : '';
        $this->ssl_key = isset($config['ssl_key']) ? $config['ssl_key'] : '';
    }

    /**
     * Set standard request parameters, generate signature, and generate interface parameter xml
     * @param array $data
     * @return string
     */
    protected function createXml($data) {
        if (!isset($data['wxappid']) && !isset($data['mch_appid']) && !isset($data['appid'])) {
            $data['appid'] = $this->appid;
        }
        if (!isset($data['mchid']) && !isset($data['mch_id'])) {
            $data['mch_id'] = $this->mch_id;
        }
        isset($data['nonce_str']) || $data['nonce_str'] = Tools::createNoncestr();
        $data["sign"] = Tools::getPaySign($data, $this->partnerKey);
        return Tools::arr2xml($data);
    }

    /**
     * POST submit XML
     * @param array $data
     * @param string $url
     * @return mixed
     */
    public function postXml($data, $url) {
        return Tools::httpPost($url, $this->createXml($data));
    }

    /**
     * Use certificate post request XML
     * @param array $data
     * @param string $url
     * @return mixed
     */
    function postXmlSSL($data, $url) {
        return Tools::httpsPost($url, $this->createXml($data), $this->ssl_cer, $this->ssl_key);
    }

    /**
     * POST submit to get Array result
     * @param array $data the data to be submitted
     * @param string $url
     * @param string $method
     * @return array
     */
    public function getArrayResult($data, $url, $method = 'postXml') {
        return Tools::xml2arr($this->$method($data, $url));
    }

    /**
     * Parse the returned result
     * @param array $result
     * @return bool|array
     */
    protected function _parseResult($result) {
        if (empty($result)) {
            $this->errCode = 'result error';
            $this->errMsg = 'Failed to parse the return result';
            return false;
        }
        if ($result['return_code'] !== 'SUCCESS') {
            $this->errCode = $result['return_code'];
            $this->errMsg = $result['return_msg'];
            return false;
        }
        if (isset($result['err_code']) && $result['err_code'] !== 'SUCCESS') {
            $this->errMsg = $result['err_code_des'];
            $this->errCode = $result['err_code'];
            return false;
        }
        return $result;
    }

    /**
     * Payment notification verification processing
     * @return bool|array
     */
    public function getNotify() {
        $notifyInfo = (array)simplexml_load_string(file_get_contents("php://input"), 'SimpleXMLElement', LIBXML_NOCDATA);
        if (empty($notifyInfo)) {
            Tools::log('Payment notification forbidden access.', 'ERR');
            $this->errCode = '404';
            $this->errMsg = 'Payment notification forbidden access.';
            return false;
        }
        if (empty($notifyInfo['sign'])) {
            Tools::log('Payment notification signature is missing.' . var_export($notifyInfo, true), 'ERR');
            $this->errCode = '403';
            $this->errMsg = 'Payment notification signature is missing.';
            return false;
        }
        $data = $notifyInfo;
        unset($data['sign']);
        if ($notifyInfo['sign'] !== Tools::getPaySign($data, $this->partnerKey)) {
            Tools::log('Payment notification signature verification failed.' . var_export($notifyInfo, true), 'ERR');
            $this->errCode = '403';
            $this->errMsg = 'Payment signature verification failed.';
            return false;
        }
        Tools::log('Payment notification signature verification success.' . var_export($notifyInfo, true), 'MSG');
        $this->errCode = '0';
        $this->errMsg = '';
        return $notifyInfo;
    }


    /**
     * Payment XML unified reply
     * @param array $data array of XML content to reply
     * @param bool $isReturn whether to return XML content, not by default
     * @return string
     */
    public function replyXml(array $data, $isReturn = false) {
        $xml = Tools::arr2xml($data);
        if ($isReturn) {
            return $xml;
        }
        ob_clean();
        exit($xml);
    }

    /**
     * Get prepaid ID
     * @param string $openid User openid, JSAPI required
     * @param string $body product title
     * @param string $out_trade_no third party order number
     * @param int $total_fee total order price
     * @param string $notify_url Payment success callback address
     * @param string $trade_type payment type JSAPI|NATIVE|APP
     * @param string $goods_tag Product tag, parameter for coupon or instant discount function
     * @return bool|string
     */
    public function getPrepayId($openid, $body, $out_trade_no, $total_fee, $notify_url, $trade_type = "JSAPI", $goods_tag = null) {
        $postdata = array(
            "body"             => $body,
            "out_trade_no"     => $out_trade_no,
            "total_fee"        => $total_fee,
            "notify_url"       => $notify_url,
            "trade_type"       => $trade_type,
            "spbill_create_ip" => Tools::getAddress()
        );
        empty($goods_tag) || $postdata['goods_tag'] = $goods_tag;
        empty($openid) || $postdata['openid'] = $openid;
        $result = $this->getArrayResult($postdata, self::MCH_BASE_URL . '/pay/unifiedorder');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return ($trade_type === 'JSAPI') ? $result['prepay_id'] : $result['code_url'];
    }

    /**
     * Get QR code prepaid ID
     * @param string $openid User openid, JSAPI required
     * @param string $body product title
     * @param string $out_trade_no third party order number
     * @param int $total_fee total order price
     * @param string $notify_url Payment success callback address
     * @param string $goods_tag Product tag, parameter for coupon or instant discount function
     * @return bool|string
     */
    public function getQrcPrepayId($openid, $body, $out_trade_no, $total_fee, $notify_url, $goods_tag = null) {
        $postdata = array(
            "body"             => $body,
            "out_trade_no"     => $out_trade_no,
            "total_fee"        => $total_fee,
            "notify_url"       => $notify_url,
            "trade_type"       => 'NATIVE',
            "spbill_create_ip" => Tools::getAddress()
        );
        empty($goods_tag) || $postdata['goods_tag'] = $goods_tag;
        empty($openid) || $postdata['openid'] = $openid;
        $result = $this->getArrayResult($postdata, self::MCH_BASE_URL . '/pay/unifiedorder');
        if (false === $this->_parseResult($result) || empty($result['prepay_id'])) {
            return false;
        }
        return $result['prepay_id'];
    }

    /**
     * Get the QR code of payment regulations
     * @param string $product_id Merchant-defined product id or order number
     * @return string
     */
    public function getQrcPayUrl($product_id) {
        $data = array(
            'appid'      => $this->appid,
            'mch_id'     => $this->mch_id,
            'time_stamp' => (string)time(),
            'nonce_str'  => Tools::createNoncestr(),
            'product_id' => (string)$product_id,
        );
        $data['sign'] = Tools::getPaySign($data, $this->partnerKey);
        return "weixin://wxpay/bizpayurl?" . http_build_query($data);
    }


    /**
     * Create JSAPI payment parameter pack
     * @param string $prepay_id
     * @return array
     */
    public function createMchPay($prepay_id) {
        $option = array();
        $option["appId"] = $this->appid;
        $option["timeStamp"] = (string)time();
        $option["nonceStr"] = Tools::createNoncestr();
        $option["package"] = "prepay_id={$prepay_id}";
        $option["signType"] = "MD5";
        $option["paySign"] = Tools::getPaySign($option, $this->partnerKey);
        $option['timestamp'] = $option['timeStamp'];
        return $option;
    }

    /**
     * close order
     * @param string $out_trade_no
     * @return bool
     */
    public function closeOrder($out_trade_no) {
        $data = array('out_trade_no' => $out_trade_no);
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/pay/closeorder');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return ($result['return_code'] === 'SUCCESS');
    }

    /**
     * Check order details
     * @param $out_trade_no
     * @return bool|array
     */
    public function queryOrder($out_trade_no) {
        $data = array('out_trade_no' => $out_trade_no);
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/pay/orderquery');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return $result;
    }

    /**
     * Order refund interface
     * @param string $out_trade_no Merchant order number
     * @param string $transaction_id WeChat order number
     * @param string $out_refund_no Merchant refund order number
     * @param int $total_fee The total amount of the merchant's order
     * @param int $refund_fee Refund amount
     * @param int|null $op_user_id operator ID, default merchant ID
     * @param string $refund_account Refund funding source
     * Only for old cash flow merchants
     * REFUND_SOURCE_UNSETTLED_FUNDS --- Unsettled funds refund (default use unsettled funds refund)
     * REFUND_SOURCE_RECHARGE_FUNDS --- available balance refund
     * @return bool
     */
    public function refund($out_trade_no, $transaction_id, $out_refund_no, $total_fee, $refund_fee, $op_user_id = null, $refund_account = '') {
        $data = array();
        $data['out_trade_no'] = $out_trade_no;
        $data['transaction_id'] = $transaction_id;
        $data['out_refund_no'] = $out_refund_no;
        $data['total_fee'] = $total_fee;
        $data['refund_fee'] = $refund_fee;
        $data['op_user_id'] = empty($op_user_id) ? $this->mch_id : $op_user_id;
        !empty($refund_account) && $data['refund_account'] = $refund_account;
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/secapi/pay/refund', 'postXmlSSL');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return ($result['return_code'] === 'SUCCESS');
    }

    /**
     * Refund query interface
     * @param string $out_trade_no
     * @return bool|array
     */
    public function refundQuery($out_trade_no) {
        $data = array();
        $data['out_trade_no'] = $out_trade_no;
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/pay/refundquery');
        if (false === $this->_parseResult($result)) {
            return false;
        }
        return $result;
    }

    /**
     * Get a statement
     * @param string $bill_date Bill date, such as 20141110
     * @param string $bill_type ALL|SUCCESS|REFUND|REVOKED
     * @return bool|array
     */
    public function getBill($bill_date, $bill_type = 'ALL') {
        $data = array();
        $data['bill_date'] = $bill_date;
        $data['bill_type'] = $bill_type;
        $result = $this->postXml($data, self::MCH_BASE_URL . '/pay/downloadbill');
        $json = Tools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }

    /**
     * Send cash red packets
     * @param string $openid red packet recipient OPENID
     * @param int $total_amount The total amount of the red envelope
     * @param string $mch_billno Merchant order number
     * @param string $sendname Merchant name
     * @param string $wishing red envelope blessing
     * @param string $act_name activity name
     * @param string $remark remark information
     * @param null|int $total_num The total number of red envelopes issued (more than 1 is a fission red envelope)
     * @param null|string $scene_id scene id
     * @param string $risk_info event information
     * @param null|string $consume_mch_id Funds Authorization Merchant ID
     * @return array|bool
     * @link https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon.php?chapter=13_5
     */
    public function sendRedPack($openid, $total_amount, $mch_billno, $sendname, $wishing, $act_name, $remark, $total_num = 1, $scene_id = null, $risk_info = '', $consume_mch_id = null) {
        $data = array();
        $data['mch_billno'] = $mch_billno; // Merchant order number mch_id+yyyymmdd+10 digits that cannot be repeated within a day
        $data['wxappid'] = $this->appid;
        $data['send_name'] = $sendname; //Business name
        $data['re_openid'] = $openid; //Red envelope recipient
        $data['total_amount'] = $total_amount; //Total amount of red envelopes
        $data['total_num'] = '1'; //issuer data
        $data['wishing'] = $wishing; //Red envelope blessing
        $data['client_ip'] = Tools::getAddress(); //The IP address of the machine calling the interface
        $data['act_name'] = $act_name; //activity name
        $data['remark'] = $remark; //Remark information
        $data['total_num'] = $total_num;
        !empty($scene_id) && $data['scene_id'] = $scene_id;
        !empty($risk_info) && $data['risk_info'] = $risk_info;
        !empty($consume_mch_id) && $data['consume_mch_id'] = $consume_mch_id;
        if ($total_num > 1) {
            $data['amt_type'] = 'ALL_RAND';
            $api = self::MCH_BASE_URL . '/mmpaymkttransfers/sendgroupredpack';
        } else {
            $api = self::MCH_BASE_URL . '/mmpaymkttransfers/sendredpack';
        }
        $result = $this->postXmlSSL($data, $api);
        $json = Tools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }


    /**
     * Cash red envelope status query
     * @param string $billno
     * @return bool|array
     * @link https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon.php?chapter=13_7&index=6
     */
    public function queryRedPack($billno) {
        $data['mch_billno'] = $billno;
        $data['bill_type'] = 'MCHT';
        $result = $this->postXmlSSL($data, self::MCH_BASE_URL . '/mmpaymkttransfers/gethbinfo');
        $json = Tools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }

    /**
    * Business payments
     * @param string $openid red packet recipient OPENID
     * @param int $amount The total amount of the red envelope
     * @param string $billno Merchant order number
     * @param string $desc remarks
     * @return bool|array
     * @link https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_2
     */
    public function transfers($openid, $amount, $billno, $desc) {
        $data = array();
        $data['mchid'] = $this->mch_id;
        $data['mch_appid'] = $this->appid;
        $data['partner_trade_no'] = $billno;
        $data['openid'] = $openid;
        $data['amount'] = $amount;
        $data['check_name'] = 'NO_CHECK'; #Do not verify name
        $data['spbill_create_ip'] = Tools::getAddress(); //The IP address of the machine calling the interface
        $data['desc'] = $desc; //Remarks
        $result = $this->postXmlSSL($data, self::MCH_BASE_URL . '/mmpaymkttransfers/promotion/transfers');
        $json = Tools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }

    /**
     * Business Payment Inquiry
     * @param string $billno
     * @return bool|array
     * @link https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_3
     */
    public function queryTransfers($billno) {
        $data['appid'] = $this->appid;
        $data['mch_id'] = $this->mch_id;
        $data['partner_trade_no'] = $billno;
        $result = $this->postXmlSSL($data, self::MCH_BASE_URL . '/mmpaymkttransfers/gettransferinfo');
        $json = Tools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }

    /**
     * The QR code link is converted into a short link
     * @param string $url long link to handle
     * @return bool|string
     */
    public function shortUrl($url) {
        $data = array();
        $data['long_url'] = $url;
        $result = $this->getArrayResult($data, self::MCH_BASE_URL . '/tools/shorturl');
        if (!$result || $result['return_code'] !== 'SUCCESS') {
            $this->errCode = $result['return_code'];
            $this->errMsg = $result['return_msg'];
            return false;
        }
        if (isset($result['err_code']) && $result['err_code'] !== 'SUCCESS') {
            $this->errMsg = $result['err_code_des'];
            $this->errCode = $result['err_code'];
            return false;
        }
        return $result['short_url'];
    }

    /**
     * Issue vouchers
     * @param int $coupon_stock_id coupon batch id
     * @param string $partner_trade_no The merchant's certificate number issued this time (format: merchant id+date+serial number), which must be unique on the merchant side
     * @param string $openid Openid information
     * @param string $op_user_id Operator account, the default is the merchant ID. The api permission corresponding to the operator can be configured on the merchant platform
     * @return bool|array
     * @link  https://pay.weixin.qq.com/wiki/doc/api/tools/sp_coupon.php?chapter=12_3
     */
    public function sendCoupon($coupon_stock_id, $partner_trade_no, $openid, $op_user_id = null) {
        $data = array();
        $data['appid'] = $this->appid;
        $data['coupon_stock_id'] = $coupon_stock_id;
        $data['openid_count'] = 1;
        $data['partner_trade_no'] = $partner_trade_no;
        $data['openid'] = $openid;
        $data['op_user_id'] = empty($op_user_id) ? $this->mch_id : $op_user_id;
        $result = $this->postXmlSSL($data, self::MCH_BASE_URL . '/mmpaymkttransfers/send_coupon');
        $json = Tools::xml2arr($result);
        if (!empty($json) && false === $this->_parseResult($json)) {
            return false;
        }
        return $json;
    }
}
