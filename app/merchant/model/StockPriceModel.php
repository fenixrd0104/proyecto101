<?php

namespace app\merchant\model;
use think\Model;


class StockPriceModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $name = 'stock_price';
    protected $readonly = ['shop_id'];
    public $orderStatus=[1=>'Entering', 2=>'Completed', 3=>'Cancelled'];


    public function getStatusAttr($value)
    {
        return $this->orderStatus[$value];
    }


    //Create Order
    public function createInventory($shop_id,$account,$uid,$remarks=''){
        $this->save([
            'shop_id'=>$shop_id,
            'account'=>$account,
            'uid'=>$uid,
            'remarks'=>$remarks,
        ]);
        return $this->id;
    }

    public function getShopName()
    {
        return $this->hasOne('ShopListsModel', 'id','shop_id')->value('name');
    }
    public function getPaginate($map){
        $limits = input('get.limit',15);
        return $this->field('think_stock_price.*,think_shop_lists.name shop_name')->join('think_shop_lists','think_shop_lists.id=think_stock_price.shop_id','left')->order('think_stock_price.id desc')->where($map)->paginate($limits);
    }

}