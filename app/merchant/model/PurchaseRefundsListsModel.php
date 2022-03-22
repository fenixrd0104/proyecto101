<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class PurchaseRefundsListsModel extends Model
{
    protected $name = 'purchase_refunds_lists';
    protected $autoWriteTimestamp = true;

    public function getLists($map){
        return  $this->field('refunds_id,refunds_price,refunds_num,remarks,think_purchase_refunds_lists.spec_id,spec_key,spec_name,spec_sku,think_purchase_refunds_lists.goods_id,goods_name,goods_sn')->join('think_spec_goods','think_purchase_refunds_lists.spec_id=think_spec_goods.spec_id','left')->join('think_goods','think_purchase_refunds_lists.goods_id=think_goods.id')->where($map)->select();
    }
}