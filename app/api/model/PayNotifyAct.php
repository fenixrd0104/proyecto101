<?php
namespace app\api\model;
use app\common\model\MoneyLog;
use app\common\model\Order;
use app\common\service\Users;


/**
 * Created by PhpStorm.
 * User: qiyu
 * Date: 2017-09-27
 * Time: 15:49
 */

class PayNotifyAct
{
    /**
     *  recharge
     * @param $order_number
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function chongzhi($order_number,$param){
        $MoneyLog = new MoneyLog();
        $info = $MoneyLog->get($order_number);
        // Judge the status of the order and then change it
       if(!empty($info) && $info->status == 0){
           //add money to the user
          $Users= new Users();
          $tag = $Users->userIncMoney($info->uid,$info->money);
           if($tag['code'] == 0){
               Log::error($tag['msg']);
               return false;
           }else{
               //change the order status
               if(!$info->save(['status'=>1])){
                   Log::error($MoneyLog->getLastSql());
                   return false;
               }
           }
       }
        return true;
    }

    /**
     * order processing
     * @param $order_number
     * @param $param
     * @return bool
     */
    public function shoporder($order_number,$param){
        $orderModel= new Order();
        //file_put_contents('./uploads/test.txt', $order_number.'-----'.serialize($param));
        $tag = $orderModel->update_pay_status($order_number, ['pay_code' => $param['param']]);
        if($tag['code'] != 1){
            Log::error($order_number.'_'.$tag['msg']);
            return false;
        }
        return true;
    }

    /**
     * Commodity purchase callback
     */

}