<?php

namespace app\merchant\model;

use think\Model;

class ShopPayConfigModel extends Model
{

    protected $name = 'shop_pay_config';
    public function getListsByWhere($map,$whereor, $Nowpage, $limits)
    {
        return $this->where($map)->whereOr($whereor)->page($Nowpage, $limits)->order('id asc')->select();
    }


}

