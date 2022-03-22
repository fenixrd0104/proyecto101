<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class PurchaseReceiptModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $name = 'purchase_receipt';
    protected $readonly = ['o_id','receiving_shop', 'supplier_id'];
    public $returnsStatus=[0=>'Not returned', 1=>'Partial return', 2=>'Full return'];
    public $receiptStatus=[0=>'to be completed', 1=>'completed', 2=>'cancelled'];

    public function getPaginate($map){
        $limits = input('get.limit',15);
        return $this->field('think_purchase_receipt.*,think_goods_supplier.supplier_name')->join('think_goods_supplier','think_purchase_receipt.supplier_id=think_goods_supplier.supplier_id')->order('think_purchase_receipt.id desc')->where($map)->paginate($limits);
    }
    public function getSumArr($map){
        return $this->field('sum(order_money) as order_money')->join('think_goods_supplier','think_purchase_receipt.supplier_id=think_goods_supplier.supplier_id')->where($map)->find();
    }
    public function getSupplier()
    {
        return $this->hasOne('GoodsSupplierModel', 'supplier_id','supplier_id')->value('supplier_name');
    }

    public function getReturnsStatusAttr($value)
    {
        return $this->returnsStatus[$value];
    }
    public function getReceiptStatusAttr($value)
    {
        return $this->receiptStatus[$value];
    }
   //Create Order
    public function createOrder($o_id,$supplier_id,$order_money,$consignee,$phone,$addr,$receiving_shop){

         $this->save([
            'o_id'=>$o_id,
            'supplier_id'=>$supplier_id,
            'order_money'=>$order_money,
            'consignee'=>$consignee,
            'phone'=>$phone,
            'addr'=>$addr,
            'receiving_shop'=>$receiving_shop,
        ]);

        return $this->id;
    }


}