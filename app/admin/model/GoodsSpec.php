<?php

namespace app\admin\model;

use think\Model;

class GoodsSpec extends Model
{
    public function goodsType()
    {
        return $this->hasOne('GoodsType', 'id', 'type_id');
    }
    public function goodsSpecItem()
    {
        return $this->hasmany('GoodsSpecItem', 'spec_id')->order('id asc');
    }
    public function afterSave($id, $items)
    {
        $items = explode("\n", trim($items));
        //construct array
        $list = array();
        foreach ($items as $k => $item) {
            if (!$item) {
                continue;
            }
            $list[$k]['spec_id'] = $id;
            $list[$k]['item']    = $item;
        }
        $list = array_values($list);
        $itemModel = new GoodsSpecItem();;
        $itemModel->where('spec_id', $id)->delete();
        $itemModel->saveAll($list);
    }

    public function afterEdit($tid, $items)
    {
        $adddata = [];
        $itemModel = new GoodsSpecItem();
        foreach ($items as $v){

            $id = key($v);
            $name = current($v);
            if($id>0){
                $itemModel->where(['id'=>$id])->update(['item'=>$name]);
            }else{
                $adddata[]=['spec_id'=>$tid,'item'=>$name];
            }
        }
        if($adddata){
            $itemModel->saveAll($adddata);
        }
    }
}

