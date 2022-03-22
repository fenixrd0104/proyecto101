<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class StockApplyModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $name = 'stock_apply';
    protected $readonly = ['from_shop', 'to_shop'];
    public $orderStatus=[1=>'to be submitted', 2=>'submitted', 3=>'cancelled', 4=>'accepted', 5=>'rejected'];
    public $deliveryStatus=[1=>'to be delivered', 2=>'partial delivery', 3=>'completed'];

    public function getPaginate($map){
        $limits = input('get.limit',15);
        return $this->field('think_stock_apply.*,a.name to_shop_name,b.name from_shop_name')->join('think_shop_lists a','think_stock_apply.to_shop=a.id','left')->join( 'think_shop_lists b','think_stock_apply.from_shop=b.id','left')->order('think_stock_apply.id desc')->where($map)->paginate($limits);
    }
    public function getSumArr($map){
        return $this->field('sum(order_money) as order_money,sum(order_num) as order_num')->join('think_shop_lists a','think_stock_apply.to_shop=a.id','left')->join( 'think_shop_lists b','think_stock_apply.from_shop=b.id','left')->where($map)->find();
    }

    public function getOrderStatusAttr($value)
    {
        return $this->orderStatus[$value];
    }
    public function getDeliveryStatusAttr($value)
    {
        return $this->deliveryStatus[$value];
    }
   //Create Order
    public function createOrder($from_shop,$to_shop,$to_uid,$to_account,$to_remarks){
      $this->save([
            'from_shop'=>$from_shop,
            'to_shop'=>$to_shop,
            'to_uid'=>$to_uid,
            'to_account'=>$to_account,
            'to_remarks'=>$to_remarks,
            'update_time'=>time(),
            'create_time'=>time(),
        ]);

        return $this->id;
    }


}