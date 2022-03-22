<?php
namespace app\api\controller;
use think\Controller;
use think\Log;
use Payment\Common\PayException;
use Payment\Client\Notify;
use app\common\model\PayConfig;

date_default_timezone_set('Asia/Shanghai');

class Snotify extends Controller
{
    public $PayConfig;
    public function index(){

        $type=input('type','');
        $this->PayConfig=new PayConfig();
        if (stripos($type, 'ali') !== false) {
           $config =$this->PayConfig->getConf('alipay');
        } elseif (stripos($type, 'wx') !== false) {
           $config =$this->PayConfig->getConf('wxpay');
        } else {
           // $config = $cmbConfig;
        }

        try {
            $callback = new PayNotify();
            $ret = Notify::run($type, $config, $callback);// Handling callbacks, signature checking is done internally


        } catch (PayException $e) {
            Log::error($e->errorMessage());
            exit;
        }
        Log::info(var_export($ret,true));
        var_dump($ret);
        exit;

    }

    public function return_url(){
        $type=input('type','');
        $this->PayConfig=new PayConfig();
        if (stripos($type, 'ali') !== false) {
            $config =$this->PayConfig->getConf('alipay');
        } elseif (stripos($type, 'wx') !== false) {
            $config =$this->PayConfig->getConf('wxpay');
        } else {
            // $config = $cmbConfig;
        }
        try {
            //TODO did not do any verification。。
            $retData = Notify::getNotifyData($type, $config);
            $callback = new PayNotify();
            $callback->frontCallBack($retData);

        } catch (PayException $e) {
            Log::error($e->errorMessage());
            echo $e->errorMessage();
            exit;
        }
    }




}




