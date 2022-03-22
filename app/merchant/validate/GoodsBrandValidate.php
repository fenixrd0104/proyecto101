<?php

namespace app\merchant\validate;

use app\admin\model\GoodsBrand;
use think\Validate;

class GoodsBrandValidate extends Validate
{
    // validation rules
    protected $rule = [
        'name|brand name'=>'require|checkName:thinkphp',
        'logo|brand logo'=> 'require',
        'url|brand URL'=>'require|url',
        'parent_cat_id|category'=>'require'
    ];
    protected function checkName($value, $rule, $data){
        $checkBrandWhere = [
            'name'=>$value,
            'parent_cat_id'=>$data['parent_cat_id'],
            'cat_id'=>$data['cat_id']
        ];
        $GoodsBrand = new GoodsBrand();
        $res = $GoodsBrand->where($checkBrandWhere)->find();
        if(isset($data['id'])){
                return true;
        }
        return !empty($res) ? false : true;
    }
}