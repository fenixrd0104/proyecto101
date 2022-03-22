<?php

namespace app\admin\model;

use think\Model;

class GoodsType extends Model
{
    /**
    * Commodity Specifications - Specification Items
     */
   /* public function GoodsSpecItem()
    {

        //hasManyThrough('Associated Model Name','Intermediate Model Name','Foreign Key Name','Intermediate Model Associated Key Name','Current Model Primary Key Name',['Model Alias ​​Definition']);
        return $this->hasManyThrough('GoodsSpecItem', 'GoodsSpec', 'type_id', 'spec_id', 'id');
    }*/

    /**
     * Product attributes
     */
    public function goodsAttribute()
    {
        return $this->hasMany('GoodsAttribute', 'type_id', 'id')->order('order asc');
    }

    /**
     * Product specifications
     */

    public function goodsSpec()
    {
        return $this->hasMany('GoodsSpec', 'type_id', 'id', 'a')->order('order asc');
    }

}

