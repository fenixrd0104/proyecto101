<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class PurchaseOrderLogModel extends Model
{
    protected $name = 'purchase_order_log';
    protected $autoWriteTimestamp = false;
    public static function operate($uid,$order_id,$remarks){
        $data=[
            'uid'=>$uid,
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