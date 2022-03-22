<?php

namespace app\admin\model;
use think\Model;


class StockInventoryModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $name = 'stock_inventory';
    protected $readonly = ['shop_id'];
    public $orderStatus=[1=>'盘点录入',2=>'盘点完成',3=>'盘点取消'];
    public $orderType=[1=>'全场盘点',2=>'抽选盘点'];

    public function getStatusAttr($value)
    {
        return $this->orderStatus[$value];
    }
    public function getTypeAttr($value)
    {
        return $this->orderType[$value];
    }


    //创建订单
    public function createInventory($shop_id,$type,$account,$uid,$remarks){
        $this->save([
            'shop_id'=>$shop_id,
            'type'=>$type,
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
        return $this->field('think_stock_inventory.*,think_shop_lists.name shop_name')->join('think_shop_lists','think_shop_lists.id=think_stock_inventory.shop_id','left')->order('think_stock_inventory.id desc')->where($map)->paginate($limits);
    }

}