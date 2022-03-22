<?php

namespace app\admin\model;

use think\Model;

class SpecGoodsModel extends Model
{
    protected $name = 'spec_goods';
    protected $pk = 'spec_id';

    public function getOne($id){
       return $this->find($id);
   }
   public function getOneDetails($spec_id){
       return $this->field('think_spec_goods.*,think_goods.goods_name,goods_sn')->join('think_goods','think_goods.id=think_spec_goods.goods_id','left')->where(['think_spec_goods.spec_id'=>$spec_id])->find();
   }
    public function getOneDetailsBySku($spec_sku){
        return $this->field('think_spec_goods.*,think_goods.goods_name,goods_sn')->join('think_goods','think_goods.id=think_spec_goods.goods_id','left')->where(['think_spec_goods.spec_sku'=>$spec_sku])->find();
    }

   //返回条码的类型
    public function getOneDetailsBarcode($spec_id){
        return $this->field('think_goods.*,think_spec_goods.*')->join('think_goods','think_goods.id=think_spec_goods.goods_id','left')->where(['think_spec_goods.spec_id'=>$spec_id])->find();
    }
}

