<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class PurchaseRefundsModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $name = 'purchase_refunds';
    protected $readonly = ['o_id','receiving_shop', 'supplier_id'];
    public $returnsStatus=[0=>'to be completed',1=>'completed',2=>'cancelled'];
    public $refundStatus=[0=>'Not refunded', 1=>'Refunded'];

    public function getPaginate($map){
        $limits = input('get.limit',15);
        return $this->field('think_purchase_refunds.*,think_goods_supplier.supplier_name')->join('think_goods_supplier','think_purchase_refunds.supplier_id=think_goods_supplier.supplier_id')->order('think_purchase_refunds.id desc')->where($map)->paginate($limits);
    }
    public function getSumArr($map){
        return $this->field('sum(order_money) as order_money')->join('think_goods_supplier','think_purchase_refunds.supplier_id=think_goods_supplier.supplier_id')->where($map)->find();
    }

    public function getSupplier()
    {
        return $this->hasOne('GoodsSupplierModel', 'supplier_id','supplier_id')->value('supplier_name');
    }

    public function getReturnsStatusAttr($value)
    {
        return $this->returnsStatus[$value];
    }
    public function getRefundStatusAttr($value)
    {
        return $this->refundStatus[$value];
    }
   //Create Order
    public function createOrder($o_id,$supplier_id,$order_money,$receiving_shop){

         $this->save([
            'o_id'=>$o_id,
            'supplier_id'=>$supplier_id,
            'order_money'=>$order_money,
            'receiving_shop'=>$receiving_shop,
        ]);

        return $this->id;
    }


}