<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class StockApplyListsModel extends Model
{
    protected $name = 'stock_apply_lists';
    protected $autoWriteTimestamp = true;

    public function getLists($map){
      return  $this->field('order_id,price,num,remarks,think_stock_apply_lists.spec_id,spec_key,spec_name,spec_sku,think_stock_apply_lists.goods_id,goods_name,goods_sn')->join('think_spec_goods','think_stock_apply_lists.spec_id=think_spec_goods.spec_id','left')->join('think_goods','think_stock_apply_lists.goods_id=think_goods.id')->where($map)->select();
    }
}