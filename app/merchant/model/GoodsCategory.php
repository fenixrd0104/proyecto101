<?php

namespace app\merchant\model;

use think\Model;

class GoodsCategory extends Model
{

    public static function onAfterInsert($goodsCategory)
    {
        $parent_id_path = "0_";
        if ($goodsCategory->parent_id) {
            $parent_id_parent_id = Self::where('id', $goodsCategory->parent_id)->value('parent_id');
            if ($parent_id_parent_id) {
                $parent_id_path .= "{$parent_id_parent_id}_{$goodsCategory->parent_id}_{$goodsCategory->id}";
            } else {
                $parent_id_path .= "{$goodsCategory->parent_id}_{$goodsCategory->id}";
            }
        } else {
            $parent_id_path .= "{$goodsCategory->id}";
        }
        $info = Self::find($goodsCategory->id);
        $info->parent_id_path = $parent_id_path;
        $info->save();
    }

    public static function onAfterUpdate($goodsCategory)
    {
        $parent_id_path = "0_";
        if ($goodsCategory->parent_id) {
            $parent_id_parent_id = Self::where('id', $goodsCategory->parent_id)->value('parent_id');
            if ($parent_id_parent_id) {
                $parent_id_path .= "{$parent_id_parent_id}_{$goodsCategory->parent_id}_{$goodsCategory->id}";
            } else {
                $parent_id_path .= "{$goodsCategory->parent_id}_{$goodsCategory->id}";
            }
        } else {
            $parent_id_path .= "{$goodsCategory->id}";
        }
        $info = Self::find($goodsCategory->id);
        $info->parent_id_path = $parent_id_path;
        $info->save();
    }
   /* protected static function init()
    {
        Self::event('after_insert', function ($goodsCategory) {
            $parent_id_path = "0_";
            if ($goodsCategory->parent_id) {
                $parent_id_parent_id = Self::where('id', $goodsCategory->parent_id)->value('parent_id');
                if ($parent_id_parent_id) {
                    $parent_id_path .= "{$parent_id_parent_id}_{$goodsCategory->parent_id}_{$goodsCategory->id}";
                } else {
                    $parent_id_path .= "{$goodsCategory->parent_id}_{$goodsCategory->id}";
                }
            } else {
                $parent_id_path .= "{$goodsCategory->id}";
            }
            $info = Self::get($goodsCategory->id);
            $info->parent_id_path = $parent_id_path;
            $info->save();
        });
        Self::event('after_update', function ($goodsCategory) {
            $parent_id_path = "0_";
            if ($goodsCategory->parent_id) {
                $parent_id_parent_id = Self::where('id', $goodsCategory->parent_id)->value('parent_id');
                if ($parent_id_parent_id) {
                    $parent_id_path .= "{$parent_id_parent_id}_{$goodsCategory->parent_id}_{$goodsCategory->id}";
                } else {
                    $parent_id_path .= "{$goodsCategory->parent_id}_{$goodsCategory->id}";
                }
            } else {
                $parent_id_path .= "{$goodsCategory->id}";
            }
            $info = Self::get($goodsCategory->id);
            $info->parent_id_path = $parent_id_path;
            $info->save();
        });
    }*/
    /**
     * @param $pid 父ID
     * @param int $is_all Whether all, the default is yes, no is only get the next level
     */
    public function getCategorySon($shopId,$pid = 0, $is_all = 1)
    {

        if ($is_all) {
            $arr = $this->where('shop_id',$shopId)->order("order asc")->select();
            return $this->sonTree($arr, $pid);
        } else {
            return $this->where(['shop_id'=>$shopId,'parent_id'=>$pid])->order('order asc')->select();
        }
    }
    public function getCategoryTree($is_show = 1)
    {
        $map = [];
        if ($is_show) {
            $map['is_show'] = 1;
        }
        $arr = $this->where($map)->order("order asc")->select();
        if ($arr) {
            $arr = $arr->toArray();
        }
        $tree = [];
        foreach ($arr as $k => $v) {
            if ($v['parent_id'] == 0) {
                foreach ($arr as $k1 => $v1) {
                    if ($v1['parent_id'] == $v['id']) {
                        foreach ($arr as $k2 => $v2) {
                            if ($v2['parent_id'] == $v1['id']) {
                                $v1['child'][] = $v2;
                            }
                        }
                        $v['child'][] = $v1;
                    }
                }
                $tree[] = $v;
            }
        }
        return $tree;
    }

    public function getCategoryTree1($is_show = 1)
    {
        $map = [];
        if ($is_show) {
            $map['is_show'] = 1;
        }
        $arr = $this->where($map)->order("order asc")->select();
        if ($arr) {
            $arr = $arr->toArray();
        }
        $tree = [];
        foreach ($arr as $k => $v) {
            if ($v['parent_id'] == 0) {
                foreach ($arr as $k1 => $v1) {
                    if ($v1['parent_id'] == $v['id']) {
                        foreach ($arr as $k2 => $v2) {
                            if ($v2['parent_id'] == $v1['id']) {
                                //$v1['child'][] = $v2;
                                $v1['children'][] = ['id'=>$v2['id'],'title'=>$v2['name'],'last'=>true,'parentId'=>$v2['parent_id']];
                            }
                        }
                        if(isset($v1['children'])){
                            $v['children'][] = ['id'=>$v1['id'],'title'=>$v1['name'],'last'=>false,'parentId'=>$v1['parent_id'],'children'=>$v1['children']];
                        }else{
                            $v['children'][] = ['id'=>$v1['id'],'title'=>$v1['name'],'last'=>true,'parentId'=>$v1['parent_id']];
                        }
                    }
                }
                $tree[] = isset($v['children'])? ['id'=>$v['id'],'title'=>$v['name'],'last'=>false,'parentId'=>0,'children'=>$v['children']]: ['id'=>$v['id'],'title'=>$v['name'],'last'=>true,'parentId'=>0];
              //  $tree[] =  ['id'=>$v['id'],'title'=>$v['name'],'last'=>false,'parentId'=>0,'children'=>$v['children']];
            }
        }
        return $tree;
    }
    protected function sonTree($arr, $pid = 0, $lev = 1)
    {
        static $Tree = array();
        foreach ($arr as $k=>$v) {
            if ($v->parent_id == $pid) {
                $v->lev = $lev;
                $Tree[] = $v;
                $this->sonTree($arr, $v->id, $lev+1);
            }
        }
        return $Tree;
    }
    public function saveCategory($data)
    {
        $add = array();
        $add['shop_id']        = $data['shop_id'];
        $add['level']          = $data['parent_id_2'] ? 3 : ($data['parent_id_1'] ? 2 : 1);
        $add['parent_id']      = $data['parent_id_2'] ? $data['parent_id_2'] :  ($data['parent_id_1'] ? $data['parent_id_1'] : 0);
        $add['mobile_name']    = $data['mobile_name'];
        $add['name']           = $data['name'];
        $add['image']          = $data['image'];
        $add['is_hot']         = $data['is_hot'];
        $add['order']          = $data['order'];
        $add['is_show']         = $data['is_show'];
        if ($data['spec_id']) {
            $data['spec_id'] = array_unique(array_filter($data['spec_id']));
            $add['spec_id_str'] = implode(',', $data['spec_id']);
        }
        if (isset($data['id']) && !empty($data['id'])) {
            $add['id'] = $data['id'];
            $this->update($add);
        } else {
            $this->save($add);
        }
    }
    public function getKeyVal(){
        return $this->where(['is_show'=>1])->order('order asc,id desc')->column('name','id');
    }
}

