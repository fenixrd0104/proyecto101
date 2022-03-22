<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class PurchaseRefundsModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $name = 'purchase_refunds';
    protected $readonly = ['o_id','receiving_shop', 'supplier_id'];
    public $returnsStatus=[0=>'待完成',1=>'已完成',2=>'已取消'];
    public $refundStatus=[0=>'未退款',1=>'已退款'];

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
   //创建订单
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