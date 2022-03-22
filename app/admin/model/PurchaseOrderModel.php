<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class PurchaseOrderModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $name = 'purchase_order';
    protected $readonly = ['receiving_shop', 'supplier_id'];
    public $orderStatus=[0=>'待完成',1=>'已完成',2=>'已取消'];
    public $receiptStatus=[0=>'待收货',1=>'部分完成',2=>'已完成'];

    public function getPaginate($map){
        $limits = input('get.limit',15);
        return $this->field('think_purchase_order.*,think_goods_supplier.supplier_name')->join('think_goods_supplier','think_purchase_order.supplier_id=think_goods_supplier.supplier_id')->order('think_purchase_order.id desc')->where($map)->paginate($limits);
    }
    public function getSumArr($map){
        return $this->field('sum(order_money) as order_money')->join('think_goods_supplier','think_purchase_order.supplier_id=think_goods_supplier.supplier_id')->where($map)->find();

    }

    public function getSupplier()
    {
        return $this->hasOne('GoodsSupplierModel', 'supplier_id','supplier_id')->value('supplier_name');
    }

    public function getOrderStatusAttr($value)
    {
        return $this->orderStatus[$value];
    }
    public function getReceiptStatusAttr($value)
    {
        return $this->receiptStatus[$value];
    }
   //创建订单
    public function createOrder($shop_id,$supplier_id,$consignee,$phone,$addr){

         $this->save([
            'supplier_id'=>$supplier_id,
            'receiving_shop'=>$shop_id,
            'consignee'=>$consignee,
            'phone'=>$phone,
            'addr'=>$addr,
            'delivery_date'=>date('Y-m-d'),
             'remarks'=>'',
        ]);

        return $this->id;
    }
    //保存
    public function saveOrder($order_id,$data){
        return $this->allowField(['supplier_id','order_status','receipt_status','order_money','consignee','phone','addr','receiving_shop','remarks','delivery_date'])->where(['id'=>$order_id])->update($data);
    }

}