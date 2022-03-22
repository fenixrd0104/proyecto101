<?php

namespace app\admin\model;
use think\Model;

class GoodsStockLog extends Model
{
   // Enable automatic writing of timestamp fields
    protected $autoWriteTimestamp = true;

    /**
     * Store merchandise inventory records
     * @param $uid
     * @param $goods_id
     * @param $spec_id
     * @param $shop_id
     * @param $type
     * @param $num
     * @param string $remark
     * @return array|string
     */
    public static function operate($uid,$goods_id,$spec_id,$shop_id,$type,$num,$price,$act=0,$remark='',$shop_stock=0,$system_stock=0){

        $data=[
            'uid'=>$uid,
            'goods_id'=>$goods_id,
            'spec_id'=>$spec_id,
            'shop_id'=>$shop_id,
            'type'=>$type,
            'num'=>$num,
            'price'=>$price,
            'act'=>$act,
            'remarks'=>$remark,
            'shop_stock'=>$shop_stock,
            'shop_stock'=>$shop_stock,
            'system_stock'=>$system_stock,
        ];
        if(self::create($data)){
            return $data;
        }else{
            return '';
        }
    }




}

