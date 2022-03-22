<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class ShopOrderLogModel extends Model
{
    protected $name = 'shop_order_log';
    protected $autoWriteTimestamp = false;
    public static function operate($uid,$user_id,$order_id,$remarks){
        $data=[
            'uid'=>$uid,
            'user_id'=>$user_id,
            'order_id'=>$order_id,
            'remarks'=>$remarks,
            'create_time'=>time()
        ];
        return self::create($data);

    }
    public static function log($order_id){
       return self::where('order_id',$order_id)->order('id desc')->select();
  }
}