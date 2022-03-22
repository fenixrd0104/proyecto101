<?php

/**

 * Created by PhpStorm.

 * User: qiyu

 * Date: 2017-09-06

 * Time: 11:24

 */



namespace app\api\controller;





use app\api\model\PayNotifyAct;

use think\Log;

use app\common\model\PayLog;

use Payment\Notify\PayNotifyInterface;







class PayNotify implements PayNotifyInterface{



    public function notifyProcess(array $data)

    {

        $PayLog = new PayLog();

        $info = $PayLog->get(['out_trade_no'=>$data['out_trade_no']]);

        if($info){

            if($info['status'] == 0){

                try {

                    $param=unserialize($info->param);

                    $PayNotifyAct=new PayNotifyAct();

                   //if($PayNotifyAct->$param['method']($info->order_number,$param['param'])){

				   if(call_user_func_array([$PayNotifyAct,$param['method']],[$info->order_number,$param['param']])){

                       $info->status=1;

                       if(!$info->save()){

                           Log::error(var_export($data,true));

                           Log::error("Order processing completed, payment record update failed");

                       }

                   }

                } catch (\Exception $e) {

                    Log::error($e->getMessage());

                }

            }



        }else{

           Log::error(var_export($data,true));

           Log::error("No relevant order number data found");

        }



        return true;



    }

    //frontend callback
    
        public function frontCallBack($data){
    
            //Get data directly from out_trade_no and then jump
    
            $PayLog = new PayLog();
    
            $info = $PayLog->get(['out_trade_no'=>$data['out_trade_no']]);
    
            // page jump

        echo  empty($info['redirect_url'])?'': "<script>window.location.href='".$info['redirect_url']."'</script>";

    }

}