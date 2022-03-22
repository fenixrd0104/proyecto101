<?php

namespace app\common\model;
use think\Model;

class PayConfig extends Model
{
    //get configured
    public function  getConf($type='wxpay'){
        $db_config= unserialize($this->where(['status'=>1,'value'=>$type])->value('config'));
        $ca_config=config('app.'.$type);
        if($type == 'wxpay'){
            $ca_config['notify_url']=url($ca_config['notify_url'][0],$ca_config['notify_url'][1],'',true);
            $ca_config['redirect_url']=url($ca_config['redirect_url'][0],$ca_config['redirect_url'][1],'',true);
            $ca_config['app_cert_pem']=app()->getRootPath().'/data/cert/'.$db_config['app_cert_pem'];
            $ca_config['app_key_pem']=app()->getRootPath().'/data/cert/'.$db_config['app_key_pem'];
        }elseif($type == 'alipay'){
            $ca_config['notify_url']=url($ca_config['notify_url'][0],$ca_config['notify_url'][1],'',true);
            $ca_config['return_url']=url($ca_config['return_url'][0],$ca_config['return_url'][1],'',true);
        }
        return array_merge($db_config,$ca_config);
    }
    public function getKeyVal($map =['status'=>1]){
        return $this->where($map)->column('name','value');
    }
}

