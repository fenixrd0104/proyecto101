<?php

namespace app\admin\model;
use think\Model;


class StockPriceListsModel extends Model
{
    protected $name = 'stock_price_lists';
    protected $autoWriteTimestamp = true;

    public function getLists($map){
      return  $this->field('think_stock_price_lists.*,spec_key,spec_name,spec_sku,goods_name,goods_sn')->join('think_spec_goods','think_stock_price_lists.spec_id=think_spec_goods.spec_id','left')->join('think_goods','think_stock_price_lists.goods_id=think_goods.id')->where($map)->select();
    }
}