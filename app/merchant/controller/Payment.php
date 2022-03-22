<?php

namespace app\merchant\controller;

use app\common\model\PayConfig;

class Payment extends Base
{
    //pay
    public function test(){
        pe(config('alipay_notify_url'));
      echo serialize( [
           'mch_id'=>'merchant ID',
            'app_id'=>'public account ID',
            'md5_key'=>'md5 key'
        ]
    );

    }

    public function index()
    {
        return json(['code'=>1,'data'=>PayConfig::order('id', 'asc')->field('id,name,logo,status')->select(),' msg'=>'']);

    }

    public function config()
    {
        $id=input('param.id');
        if(request()->isPost() && !empty($id)){
            $data=request()->post();
            if(PayConfig::where(['id'=>$id])->update(['config'=>serialize($data)])){
                return json(['code' => 1, 'data' => 1, 'msg' => 'update successful']);
            }else{
                return json(['code' => 0, 'data' => 1, 'msg' => 'update failed']);
            }
        }
        $res = PayConfig::find($id);

        return json(['code'=>1,'data'=>[
            'title'=>$res->name,
            'param'=>unserialize($res->param),
            'config'=>unserialize($res->config)
        ],'msg'=>'']);



    }

    public function status()
    {

        $id = input('param.id');
        $res = PayConfig::find($id);
        if ($res->status == 1) {
            $res->status = 0;
            $flag = $res->save();
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        } else {
            $res->status = 1;
            $flag = $res->save();
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'enabled']);
        }


    }


}