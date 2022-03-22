<?php

namespace app\common\service;

use app\common\model\PayConfig;
use app\common\model\PayLog;
use app\common\model\RefundsLog;
use Payment\Client\Transfer;
use think\Model;
use think\Log;
use Payment\Common\PayException;
use Payment\Client\Charge;
use Payment\Config;
use Payment\Client\Refund;
use Payment\Client\Query;
class Payment extends Model
{
    public $PayConfig;//configure
    public function __construct()
    {
        parent::__construct();
        $this->PayConfig=new PayConfig();
    }

    /*
  $data = [
           'out_trade_no' => '14935460661343',
           'refund_fee' => '0.01',
           'reason' => 'Test account refund',
           'refund_no' => $refundNo,
       ];


   $data = [
   'out_trade_no' => '14935385689468',
   'total_fee' => '3.01',
   'refund_fee' => '3.01',
   'refund_no' => $refundNo,
   'refund_account' => WxConfig::REFUND_RECHARGE,// REFUND_RECHARGE: Refund of available balance REFUND_UNSETTLED: Refund of unsettled funds (default)
   ];

 */
    /**
     * Refund operation
     * @param $order_sn
     * @param $money 0 means all
     */
    public function refunds($order_sn,$money=0,$reason='Order refund'){
        $info=PayLog::getOne(['order_number'=>$order_sn,'status'=>1]);
// return $info;
        if($info){
            // refund amount
            $refunds = $info->money;
            if($money>0){
                $refunds = $money;
            }
            if($refunds>$info->money){
                return ['code'=>-2,'msg'=>'The refund amount cannot be greater than the payment amount'];
            }

            $refundNo=$this->create_order_no();
            $tag=['code'=>0];
            //Determine payment type
            switch($info->pay_type){
                case 3://alipay
                    $data = [
                        'out_trade_no' => $info->out_trade_no,
                        'refund_fee' => $refunds,
                        'reason' => $reason,
                        'refund_no' => $refundNo,
                    ];
                    $tag = $this->ali_refund($data);
                    break;
                case 2://WeChat payment
                    $data = [
                        'out_trade_no' => $info->out_trade_no,
                        'total_fee' => $info->money,
                        'refund_fee' => $refunds,
                        'refund_no' => $refundNo,
                        'refund_account' => 'REFUND_SOURCE_UNSETTLED_FUNDS',// REFUND_RECHARGE: available balance refund REFUND_UNSETTLED: unsettled funds refund (default)
                    ];
                    $tag = $this->wx_refund($data);
                    break;
            }
            if($tag['code'] == 1){
                RefundsLog::create([
                    'out_trade_no'=>$refundNo,
                    'pay_trade_no'=>$info->out_trade_no,
                    'order_number'=>$order_sn,
                    'uid'=>$info->uid,
                    'money'=>$refunds,
                    'pay_type'=>$info->pay_type,
                    'status'=>'1',
                    'reason'=>$reason
                ]);
               //Change the status of the order payment
                $info->status=2;
                $info->save();

                return ['code'=>1,'msg'=>'refund successful'];
            }else{
                return $tag;
            }



        }else{
            return ['code'=>-1,'msg'=>'No payment order information'];
        }
    }

    /**
     * Ali WAP payment
     * @param $uid User ID
     * @param $subject item title
     * @param $order_id order number
     * @param $amount amount
     * @param $param callback parameter after success
     * @param string $redirect_url redirect URL after success
     * @param string $body product description
     * @param int $goods_type Goods type 1 physical 0 virtual
     * @return array
     */
    public function ali_wap($uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){
        //Create order information
        $payData=$this->ali_handle(21,$uid,$subject,$order_id,$amount,$param,$redirect_url,$body,$goods_type);
        try {
            $url = Charge::run(Config::ALI_CHANNEL_WAP, $this->PayConfig->getConf('alipay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $url, 'msg' => ''];

    }
    /**
    * Ali PC payment
     * @param $uid User ID
     * @param $subject item title
     * @param $order_id order number
     * @param $amount amount
     * @param $param callback parameter after success
     * @param string $redirect_url redirect URL after success
     * @param string $body product description
     * @param int $goods_type Goods type 1 physical 0 virtual
     * @return array URL to jump to after success
     */
    public function ali_web($uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){
        //Create order information
        $payData=$this->ali_handle(22,$uid,$subject,$order_id,$amount,$param,$redirect_url,$body,$goods_type);
        try {
            $url = Charge::run(Config::ALI_CHANNEL_WEB, $this->PayConfig->getConf('alipay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $url, 'msg' => ''];
    }
    /**
    * Ali QR code
     * @param $uid User ID
     * @param $subject item title
     * @param $order_id order number
     * @param $amount amount
     * @param $param callback parameter after success
     * @param string $redirect_url redirect URL after success
     * @param string $body product description
     * @param int $goods_type Goods type 1 physical 0 virtual
     * @return array The content of the QR code to be generated
     */
    public function ali_qr($uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){
        //Create order information
        $payData=$this->ali_handle(23,$uid,$subject,$order_id,$amount,$param,$redirect_url,$body,$goods_type);
        try {
            $url = Charge::run(Config::ALI_CHANNEL_QR, $this->PayConfig->getConf('alipay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $url, 'msg' => ''];
    }
    /**
     * Ali APP payment
     * @param $uid User ID
     * @param $subject item title
     * @param $order_id order number
     * @param $amount amount
     * @param $param callback parameter after success
     * @param string $redirect_url redirect URL after success
     * @param string $body product description
     * @param int $goods_type Goods type 1 physical 0 virtual
     * @return array
     */
    public function ali_app($uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){
        //Create order information
        $payData=$this->ali_handle(24,$uid,$subject,$order_id,$amount,$param,$redirect_url,$body,$goods_type);

        try {
            $str = Charge::run(Config::ALI_CHANNEL_APP, $this->PayConfig->getConf('alipay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $str, 'msg' => ''];

    }


    /***
     * @param $uid User ID
     * @param $body product details
     * @param $subject product description
     * @param $order_id order number
     * @param $amount amount
     * @param $auth_code authorization code
     * @param string $store_id store id
     * @param string $operator_id cashier ID
     * @param string $terminal_id store ID
     * @return array *
     */
    public function ali_bar($uid,$body,$subject,$order_id,$amount,$auth_code,$store_id='',$operator_id='',$terminal_id=''){
        //Create order information
        $payData=$this->ali_handle_bar(25,$uid,$body,$subject,$order_id,$amount,$auth_code,$store_id='',$operator_id='',$terminal_id='');
        try {
            $ret = Charge::run(Config::ALI_CHANNEL_BAR, $this->PayConfig->getConf('alipay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $ret, 'msg' => ''];
    }
    public function ali_query($out_trade_no){
        $data = [
            'out_trade_no' => $out_trade_no,
            //'trade_no' => '2017090221001004350200242476',
        ];
        try {
            $ret = Query::run(Config::ALI_CHARGE, $this->PayConfig->getConf('alipay'), $data);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $ret, 'msg' => ''];
    }
    public function ali_close($out_trade_no){
        $data = [
            'out_trade_no' => $out_trade_no,
            //'trade_no' => '2017090221001004350200242476',
        ];
        try {
            $ret = Query::run(Config::ALI_CLOSE, $this->PayConfig->getConf('alipay'), $data);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        if($ret['code'] == 1000){
            return ['code' => 1, 'data' => $ret, 'msg' => 'Close successfully'];
        }
        return ['code' => 0, 'data' => $ret, 'msg' => 'Failed to close'];
    }


    protected function ali_handle_bar($pay_type,$uid,$body,$subject,$order_id,$amount,$auth_code,$store_id='',$operator_id='',$terminal_id=''){
        //Create payment order number
        $out_trade_no=$this->create_order_no();
        //Add payment information
        PayLog::create([
            'out_trade_no'=>$out_trade_no,
            'uid'=>$uid,
            'money'=>$amount,
            'order_number'=>$order_id,
            'pay_type'=>$pay_type,
            'param'=>serialize(''),
            'redirect_url'=>'',
        ]);
        $payData = [
            'body' => $body,
            'subject' => $subject,
            'order_no' => $out_trade_no,
            'timeout_express' => time() + 600,// means payment must be made within 600s
            'amount' => $amount,// The unit is yuan, the minimum is 0.01
            'return_param' => '',
            // 'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',// client address
            'goods_type' => '1',// 0—virtual goods, 1—physical goods
            'store_id' => $store_id,
            'operator_id' =>$operator_id,//Cashier
            'terminal_id' => $terminal_id,// terminal equipment number (store number or cashier equipment ID) default value web
            'scene' => 'bar_code',// barcode payment: bar_code sound wave payment: wave_code
            'auth_code' => $auth_code,
        ];
        return  $payData;
    }

    /**
    Ali payment processing
    $payData = [
    'body' => 'ali qr pay',
    'subject' => 'Test Alipay scan code payment',
    'order_no' => $orderNo,
    'timeout_express' => time() + 600,// means payment must be made within 600s
    'amount' => '0.01',// The unit is yuan, the minimum is 0.01
    'return_param' => '123123',
    'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',// client address
    'goods_type' => '1',
    'store_id' => '',
    ];
     */
    private function ali_handle($pay_type,$uid,$subject,$order_id,$amount,$param,$redirect_url='',$body='',$goods_type=1){
       //Create payment order number
        $out_trade_no=$this->create_order_no();
        //Add payment information
        PayLog::create([
            'out_trade_no'=>$out_trade_no,
            'uid'=>$uid,
            'money'=>$amount,
            'order_number'=>$order_id,
            'pay_type'=>$pay_type,
            'param'=>serialize($param),
            'redirect_url'=>$redirect_url,
        ]);
        switch ($pay_type){
            case 25://barcode payment
                $payData = [
                    'body' => 'ali bar pay',
                    'subject' => 'Test Alipay barcode payment',
                    'order_no' => $out_trade_no,
                    'timeout_express' => time() + 600,// means payment must be made within 600s
                    'amount' => $amount,// The unit is yuan, the minimum is 0.01
                    'return_param' => '',
                    // 'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',// client address
                    'goods_type' => '1',// 0—virtual goods, 1—physical goods
                    'store_id' => '',

                    'operator_id' => '',
                    'terminal_id' => '',// terminal equipment number (store number or cashier equipment ID) default value web
                    'scene' => 'bar_code',// barcode payment: bar_code sound wave payment: wave_code
                    'auth_code' => '12312313123',
                ];
                break;
            default:
                $payData=[
                    'body'    => $body,
                    'subject'    =>$subject,
                    'order_no'    => $out_trade_no,
                    'timeout_express' => time() + 600,// means payment must be made within 600s
                    'amount' => $amount,// The unit is yuan, the minimum is 0.01
                    'return_param' => '',
                    'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',// client address
                    'goods_type' => $goods_type,
                    'store_id' => ''
                ];
                break;

        }
        return  $payData;
    }

    /*
      $data = [
              'out_trade_no' => '14935460661343',
               'refund_fee' => '0.01',
               'reason' => 'Test account refund',
               'refund_no' => $refundNo,
           ];


       $data = [
       'out_trade_no' => '14935385689468',
       'total_fee' => '3.01',
       'refund_fee' => '3.01',
       'refund_no' => $refundNo,
       'refund_account' => WxConfig::REFUND_RECHARGE,// REFUND_RECHARGE: available balance refund REFUND_UNSETTLED: unsettled funds refund (default)
       ];

     */
    /**
     * Ali refund
     * @param $data
     * @return array
     */
    public function ali_refund($data){
        try {
            $ret = Refund::run(Config::ALI_REFUND, $this->PayConfig->getConf('alipay'), $data);
            if($ret['code']=='10000'){
                return ['code' => 1, 'data' => $ret, 'msg' => 'refund successful'];
            }else{
                return ['code' => 0, 'data' => $ret, 'msg' => $ret['msg']];
            }
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }


    }




    /**
     * WeChat public account payment
     * @param $openid
     * @param $uid
     * @param $subject
     * @param $order_id
     * @param $amount
     * @param $param
     * @param string $body
     * @param int $product_id
     * @return array
     */
    public function wx_pub($openid,$uid,$subject,$order_id,$amount,$param,$body='Online payment',$product_id=1){
        //create order info
        $payData=$this->wx_handle(11,'wx_pub',$openid,$uid,$subject,$order_id,$amount,$param,$body,$product_id);
        try {
            $ret = Charge::run(Config::WX_CHANNEL_PUB, $this->PayConfig->getConf('wxpay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $ret, 'msg' => ''];

    }
    /**
     * WeChat h5 payment
     * @param $uid User ID
     * @param $subject product title
     * @param $order_id order ID
     * @param $amount amount
     * @param $param callback after success
     * @param string $body product description
     */
    public function wx_wap($uid,$subject,$order_id,$amount,$param,$body='Online payment'){
        $payData=$this->wx_handle(12,'wx_wap','',$uid,$subject,$order_id,$amount,$param,$body,$product_id=1);
        try {
            $url = Charge::run(Config::WX_CHANNEL_WAP, $this->PayConfig->getConf('wxpay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $url, 'msg' => ''];

    }

    /**
     * WeChat APP
     * @param $uid User ID
     * @param $subject product name
     * @param $order_id order ID
     * @param $amount amount
     * @param $param callback parameter
     * @param string $body product description
     */
    public function wx_app($uid,$subject,$order_id,$amount,$param,$body='Online payment'){
        //create order info
        $payData=$this->wx_handle(13,'wx_app','',$uid,$subject,$order_id,$amount,$param,$body,1);
        try {
            $conf=$this->PayConfig->getConf('wxapp');
            $conf['notify_url']=$conf['notify_url'].'/fen/app';
            $ret = Charge::run(Config::WX_CHANNEL_APP, $conf, $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $ret, 'msg' => ''];

    }

    /**Mini program payment */
    public function wx_lite($openid,$uid,$subject,$order_id,$amount,$param,$body='Online payment',$product_id=1){

        //create order info
        $payData=$this->wx_handle(14,'wx_pub',$openid,$uid,$subject,$order_id,$amount,$param,$body,$product_id);
        try {
            $conf=$this->PayConfig->getConf('wxlite');
            $conf['notify_url']=$conf['notify_url'].'/fen/lite';
            $ret = Charge::run(Config::WX_CHANNEL_LITE, $conf, $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $ret, 'msg' => ''];

    }
    /**
     * Wechat QR code
     * @param $openid
     * @param $uid
     * @param $subject
     * @param $order_id
     * @param $amount
     * @param $param
     * @param string $body
     * @param int $product_id
     * @return array The URL that needs to generate the QR code
     */
    public function wx_qr($openid,$uid,$subject,$order_id,$amount,$param,$body='Online payment',$product_id=1){
        $payData=$this->wx_handle(15,'wx_qr',$openid,$uid,$subject,$order_id,$amount,$param,$body,$product_id);
        try {
            $ret = Charge::run(Config::WX_CHANNEL_QR, $this->PayConfig->getConf('wxpay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
        return ['code' => 1, 'data' => $ret, 'msg' => ''];

    }



    public function wx_bar($uid,$body,$subject,$order_id,$amount,$auth_code,$terminal_id='web',$param=''){
        $payData=$this->wx_handle(16,'wx_bar','',$uid,$subject,$order_id,$amount,$param,$body);
        $payData['terminal_id']=$terminal_id;
        $payData['auth_code']=$auth_code;
        try {
            $ret = Charge::run(Config::WX_CHANNEL_BAR, $this->PayConfig->getConf('wxpay'), $payData);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];

        }
        return ['code' => 1, 'data' => $ret, 'msg' => ''];

    }
    public function wx_query($out_trade_no){
        $data = [
            'out_trade_no' => $out_trade_no,
         //   'transaction_id' => '20170430190922203640695',
        ];
        try {
            $ret = Query::run(Config::WX_CHARGE, $this->PayConfig->getConf('wxpay'), $data);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }

        return ['code' => 1, 'data' => $ret, 'msg' => ''];
    }

    public function wx_close($out_trade_no){
        $data = [
            'out_trade_no' => $out_trade_no,
            //'trade_no' => '2017090221001004350200242476',
        ];
        try {
            $ret = Query::run(Config::WX_CLOSE, $this->PayConfig->getConf('wxpay'), $data);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }

       if(strtoupper($ret['return_code']) == 'SUCCESS' && strtoupper($ret['return_code']) == 'SUCCESS'){
            return ['code' => 1, 'data' => $ret, 'msg' => 'Close successfully'];
        }
        return ['code' => 0, 'data' => $ret, 'msg' => 'Failed to close'];
    }

    /**
     * WeChat data processing
     * @param $type
     * @param $openid
     * @param $uid
     * @param $subject
     * @param $order_id
     * @param $amount
     * @param $param
     * @param string $body
     * @param int $product_id
     * @return array
     */
    private function wx_handle($pay_type,$type,$openid,$uid,$subject,$order_id,$amount,$param,$body='online payment',$product_id=1){
        //Create payment order number
        $out_trade_no=$this->create_order_no();
        //Add payment information
        PayLog::create([
            'out_trade_no'=>$out_trade_no,
            'uid'=>$uid,
            'money'=>$amount,
            'order_number'=>$order_id,
            'pay_type'=>$pay_type,
            'param'=>serialize($param),
            'redirect_url'=>'',
        ]);
        $payData=[];
        $client_ip=isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';// client address
        switch($type){
            case 'wx_app':
                $payData=[
                    'body' => $body,
                    'subject' => $subject,
                    'order_no' => $out_trade_no,
                    'timeout_express' => time() + 600,// means payment must be made within 600s
                    'amount' => $amount,// The unit is yuan, the minimum is 0.01
                    'return_param' => '',
                    'client_ip' => $client_ip,// client address
                ];
                break;
            case 'wx_wap':
                $payData=[
                  'body' => $body,
                    'subject' => $subject,
                    'order_no' => $out_trade_no,
                    'timeout_express' => time() + 600,// means payment must be made within 600s
                    'amount' => $amount,// The unit is yuan, the minimum is 0.01
                    'return_param' => '',
                    'client_ip' => $client_ip,// client address
                    'scene_info' => [
                        'type' => 'Wap',// IOS Android Wap Tencent recommends IOS ANDROID to use app payment
                        'wap_url' => 'helei112g.github.io',//Your own wap address
                        'wap_name' => 'Test recharge',
                    ]
                ];
                break;
            case 'wx_pub':
                $payData=[
                    'body'    => $body,
                    'subject'    =>$subject,
                    'order_no'    => $out_trade_no,
                   'timeout_express' => time() + 600,// means payment must be made within 600s
                    'amount' => $amount,// The unit is yuan, the minimum is 0.01
                    'return_param' => '',
                    'client_ip' => $client_ip,// client address
                    'openid' => $openid,
                    'product_id' => $product_id,
                    'store_id' => ''
                ];
                break;
            case 'wx_qr':
                $payData=[
                    'body' => $body,
                    'subject' => $subject,
                    'order_no' => $out_trade_no,
                    'timeout_express' => time() + 600,// means payment must be made within 600s
                    'amount' => $amount,// The unit is yuan, the minimum is 0.01
                    'return_param' => '',
                    'client_ip' => $client_ip,// client address
                    'openid' => $openid,
                    'product_id' =>$product_id,
                ];
                break;
            case 'wx_bar':
                $payData=[
                    'body'    => $body,
                    'subject'    =>$subject,
                    'order_no'    => $out_trade_no,
                   'timeout_express' => time() + 600,// means payment must be made within 600s
                    'amount' => $amount,// The unit is yuan, the minimum is 0.01
                    'return_param' => '',
                    'client_ip' => $client_ip,// client address
                    'terminal_id' =>'',// terminal equipment number (store number or cashier equipment ID) default value web
                    'auth_code' => '',
                ];
                break;

        };
        return $payData;
    }

    /**
    $data = [
    'out_trade_no' => '14935385689468',
    'total_fee' => '3.01',
    'refund_fee' => '3.01',
    'refund_no' => $refundNo,
   'refund_account' => WxConfig::REFUND_RECHARGE,// REFUND_RECHARGE: available balance refund REFUND_UNSETTLED: unsettled funds refund (default)
    ];
     */
    /**
     * WeChat refund
     * @param $data
     * @return array
     */
    public function wx_refund($data){

        try {
            $ret = Refund::run(Config::WX_REFUND, $this->PayConfig->getConf('wxpay'), $data);
            Log::error($ret);
            if($ret['return_code']=='SUCCESS'){
                return ['code' => 1, 'data' => $ret, 'msg' => 'Refund successfully'];
            }else{
                return ['code' => 0, 'data' => $ret, 'msg' => $ret['msg']];
            }

        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }
    }

    public function wx_transfer($data){
        try {
            $ret = Transfer::run(Config::WX_TRANSFER, $this->PayConfig->getConf('wxpay'), $data);
        } catch (PayException $e) {
            return ['code' =>0, 'data' => '', 'msg' =>  $e->errorMessage()];
        }


        if(isset($ret['return_code']) && $ret['return_code'] =='FAIL' ){
            return ['code'=>0,'msg'=>$ret['return_msg']];
        }

        if(isset($ret['return_code']) && $ret['return_code'] =='SUCCESS' && isset($ret['result_code']) && $ret['result_code'] =='SUCCESS' ){

            return ['code'=>1,'data'=>$ret['payment_no'],'msg'=>''];
        }

        return ['code'=>0,'msg'=>json_encode($ret)];

    }




    /**
     * Create payment order number
     * @return string
     */
    private function create_order_no(){
        return time() . rand(1000, 9999);
    }






}

