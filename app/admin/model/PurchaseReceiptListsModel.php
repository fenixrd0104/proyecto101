<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class PurchaseReceiptListsModel extends Model
{
    protected $name = 'purchase_receipt_lists';
    protected $autoWriteTimestamp = true;

    public function getLists($map){
        return  $this->field('receipt_id,receipt_price,receipt_num,remarks,think_purchase_receipt_lists.spec_id,spec_key,spec_name,spec_sku,think_purchase_receipt_lists.goods_id,goods_name,goods_sn')->join('think_spec_goods','think_purchase_receipt_lists.spec_id=think_spec_goods.spec_id','left')->join('think_goods','think_purchase_receipt_lists.goods_id=think_goods.id')->where($map)->select();
    }
}