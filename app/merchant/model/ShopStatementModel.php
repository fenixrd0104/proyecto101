<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class ShopStatementModel extends Model
{
    protected $name = 'shop_statement';
    protected $autoWriteTimestamp = true;
    public $StatusArr=[1=>'To be confirmed', 2=>'Confirmed', 3=>'Cancelled'];
    public function getStatusAttr($value)
    {
        return $this->StatusArr[$value];
    }
    public static function operate($uid,$shop_id,$order_id,$pay_id,$pay_name,$money,$remark=''){
        $data=[
            'uid'=>$uid,
            'shop_id'=>$shop_id,
            'order_id'=>$order_id,
            'pay_id'=>$pay_id,
            'pay_name'=>$pay_name,
            'money'=>$money,
            'date_tag'=>date('Y-m-d'),
            'remark'=>$remark,
            'update_time'=>time(),
            'create_time'=>time()
        ];
        return self::create($data);
    }
    public function getPaginate($map,$limits){
        return  $this->field('think_shop_statement.*,think_shop_lists.name shop_name')->join('think_shop_lists','think_shop_statement.shop_id=think_shop_lists.id','left')->order('id desc')->where($map)->paginate($limits);
    }
    public function getSumArr($map){
        return  $this->field('sum(total_count) as total_count,sum(total_money) as total_money,sum(return_count) as return_count,sum(return_money) as return_money,sum(all_count) as all_count,sum(real_money) as real_money')->join('think_shop_lists','think_shop_statement.shop_id=think_shop_lists.id','left')->where($map)->find();
    }
}

