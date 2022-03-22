<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class ShopBIllModel extends Model
{
    protected $name = 'shop_bill';
    protected $autoWriteTimestamp = true;
    public static function operate($uid,$account,$shop_id,$order_id,$pay_id,$pay_name,$money,$user_id,$user_account,$remark=''){
        $data=[
            'uid'=>$uid,
            'account'=>$account,
            'shop_id'=>$shop_id,
            'order_id'=>$order_id,
            'pay_id'=>$pay_id,
            'pay_name'=>$pay_name,
            'money'=>$money,
            'date_tag'=>date('Y-m-d'),
            'remark'=>$remark,
            'user_id'=>$user_id,
            'user_account'=>$user_account,
            'update_time'=>time(),
            'create_time'=>time()
        ];
        return self::create($data);
    }
    public function getPaginate($map,$limits){
        return  $this->field('think_shop_bill.*,think_shop_lists.name shop_name')->join('think_shop_lists','think_shop_bill.shop_id=think_shop_lists.id','left')->order('id desc')->where($map)->paginate($limits);
    }
    public function getStatement($uid,$shop_id,$start_time,$end_time){
        //总收款
        $total = $this->field('count(money) count,sum(money) money')->where([
            ['uid','=',$uid],
            ['shop_id','=',$shop_id],
            ['money','>',0],
            ['create_time','>=',$start_time],
            ['create_time','<=',$end_time],
        ])->find();
        //总退款
        $return = $this->field('count(money) count,sum(money) money')->where([
            ['uid','=',$uid],
            ['shop_id','=',$shop_id],
            ['money','<',0],
            ['create_time','>=',$start_time],
            ['create_time','<=',$end_time],
        ])->find();

        //对账金额
        $real= $this->field('count(money) count,sum(money) money')->where([
            ['uid','=',$uid],
            ['shop_id','=',$shop_id],
            ['create_time','>=',$start_time],
            ['create_time','<=',$end_time],
       ])->find();
        //明细
        $lists = $this->field('pay_name,count(money) count,sum(money) money')->where([
            ['uid','=',$uid],
            ['shop_id','=',$shop_id],
            ['create_time','>=',$start_time],
            ['create_time','<=',$end_time],
        ])->group('pay_name')->select();

        if(!$return['money']){$return['money']=0;}
        if(!$total['money']){$total['money']=0;}
        if(!$real['money']){$real['money']=0;}
        return [
            'total_count'=>$total['count'],
            'total_money'=>$total['money'],
            'return_count'=>$return['count'],
            'return_money'=>$return['money'],
            'all_count'=>$real['count'],
            'real_money'=>$real['money'],
            'lists'=>$lists
        ];
    }
    public function getSumArr($map){
        return  $this->field('sum(money) as money')->join('think_shop_lists','think_shop_bill.shop_id=think_shop_lists.id','left')->where($map)->find();

    }
}

