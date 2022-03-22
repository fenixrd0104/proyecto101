<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class StockReceiptModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $name = 'stock_receipt';
    protected $readonly = ['o_id','to_shop', 'from_shop'];
    public $receiptStatus=[0=>'to be completed', 1=>'completed', 2=>'cancelled'];

    public function getPaginate($map){
        $limits = input('get.limit',15);

        return $this->field('think_stock_receipt.*,a.name to_shop_name,b.name from_shop_name')->join('think_shop_lists a','think_stock_receipt.to_shop=a.id','left')->join( 'think_shop_lists b','think_stock_receipt.from_shop=b.id','left')->order('think_stock_receipt.id desc')->where($map)->paginate($limits);
    }
    public function getSumArr($map){
        return $this->field('sum(order_money) as order_money,sum(order_num) as order_num')->join('think_shop_lists a','think_stock_receipt.to_shop=a.id','left')->join( 'think_shop_lists b','think_stock_receipt.from_shop=b.id','left')->order('think_stock_receipt.id desc')->where($map)->find();
    }
    public function getStatusAttr($value)
    {
        return $this->receiptStatus[$value];
    }
   //Create Order
    public function createOrder($o_id,$order_money,$to_shop,$from_shop,$remarks){
         $this->save([
            'o_id'=>$o_id,
            'order_money'=>$order_money,
            'to_shop'=>$to_shop,
            'from_shop'=>$from_shop,
            'remarks'=>$remarks,
        ]);
        return $this->id;
    }


}