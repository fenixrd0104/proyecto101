<?php

namespace app\admin\model;
use think\Model;

class GoodsPriceLog extends Model
{
    // Enable automatic writing of timestamp fields
    protected $autoWriteTimestamp = true;

    /**
     * Store merchandise inventory records
     * @param $uid
     * @param $goods_id
     * @param $spec_id
     * @param $shop_id
     * @param $type  1
     * @param $num
     * @param string $remark
     * @return array|string
     */
    public static function operate($uid,$goods_id,$spec_id,$shop_id,$market_price,$new_market_price,$shop_price,$new_shop_price,$trade_price,$new_trade_price,$cost_price,$new_cost_price,$type=0,$act=0,$remark=''){

        $data=[
            'uid'=>$uid,
            'goods_id'=>$goods_id,
            'spec_id'=>$spec_id,
            'shop_id'=>$shop_id,
            'market_price'=>$market_price,
            'new_market_price'=>$new_market_price,
            'shop_price'=>$shop_price,
            'new_shop_price'=>$new_shop_price,
            'trade_price'=>$trade_price,
            'new_trade_price'=>$new_trade_price,
            'cost_price'=>$cost_price,
            'new_cost_price'=>$new_cost_price,
            'type'=>$type,
            'act'=>$act,
            'remark'=>$remark,
        ];
        if(self::create($data)){
            return $data;
        }else{
            return '';
        }
    }




}

