<?php
/**
* public goods
 * User: tianfeiwen
 * Date: 2017/9/19
 * Time: 14:47
 */
namespace app\common\model;

use think\Model;
use think\facade\Db;
use app\admin\model\GoodsCategory;

class Goods extends Model
{
    /**
     * @param int $cat_id category
     * @param int $brand_id the brand
     * @param int $recom_type type '' all 1is_new 2is_hot 3re_recommend
     * @param $keywords Product keywords
     * @param $is_spec distinguish between spec specifications 0 does not distinguish, default 1 distinguishes
     * @param $is_on_sale -1 all, default 1 listed 0 not listed
     * @return item list
     */
    public function getGoodsList($cat_id = 0, $brand_id = 0, $recom_type = '', $keywords = '', $is_spec = 0, $is_on_sale = -1)
    {
        //assembly filter
        $map = [];
        if ($cat_id) {
            $GoodsCategory = new GoodsCategory();
            $catSon = $GoodsCategory->getCategorySon($cat_id);
            $cats = [];
            foreach ($catSon as $v) {
                $cats[] = $v->id;
            }
            $cats[] = $cat_id;
            $map['cat_id'] = ['in', $cats];
        }
        $brand_id && $map['brand_id'] = $brand_id;
        in_array($recom_type, ['is_new', 'is_hot', 'is_recommend']) && $map[$recom_type] = 1;
        $keywords && $map['goods_name'] = ['like', '%' . $keywords . '%'];
        $is_on_sale != -1 && $map['is_on_sale'] = $is_on_sale;//Whether it is on the shelf
        $list = Db::name('goods')->where($map)->select();
        if ($list) {
            if ($is_spec) {
                // Need to find out all the specifications of the product
                foreach ($list as $k => $v) {
                    //Check if there are specifications
                    $list[$k]['spec_list'] = Db::name('spec_goods')->where('goods_id', $v['id'])->select();
                }
            }
            return ['code' => 1, 'msg' => 'query successful', 'data' => $list];
        }
        return ['code' => 0, 'msg' => 'no data', 'data' => ''];
    }

    /**'
     * @param $goods_id_arr array of commodity prices
     * @param int $lev how many levels
     * @return array
     */
    public function get_filter_price($goods_id_arr, $lev = 5)
    {
        if (!$goods_id_arr) {
            return [];
        }
        $price_arr = Db::name('goods')->where('id','in', $goods_id_arr)->column('shop_price');
        $max = ceil(max($price_arr));
        $min = ceil(min($price_arr));
        $p = $max - $min;
        if ($p <= 0) {
            return [];
        }
        $psize = ceil($p / $lev);
        for ($i = 0; $i < 5; $i++) {
            $s   = $min + $i * $psize;
            $e   = $s + $psize - 1;
            if ($i == 4) {
                $e = $max;
            }
            foreach ($price_arr as $v) {
                if ($v >= $s && $v <= $e) {
                    $parr[] = ['name' => $s .'-'. $e .'元', 'value' => $s .'_'.$e, 'key' => 'price'];
                    break;
                }
            }
        }
        return $parr;
    }

    public function get_filter_brand($goods_id_arr)
    {
        if (!$goods_id_arr) {
            return [];
        }

        $brand_arr = Db::name('goods')->where('id','in', $goods_id_arr)->column('brand_id');
        if (!$brand_arr) {
            return [];
        }
        $brand_arr = array_unique($brand_arr);
        $brand_list = Db::name('goods_brand')->field('id as value, name')->where('id','in', $brand_arr)->select();
        foreach ($brand_list as $k => $v) {
            $v['key']= 'brand';
            $brand_list[$k] = $v;
        }
        return $brand_list;
    }

    public function get_filter_spec($tid, $goods_id_arr)
    {
        if (!$goods_id_arr) {
            return [];
        }
        //Whether the category has a filter attribute set
        // $spec_id_str = Db::name('goods_category')->where('id', $tid)->value('spec_id_str');
        // if (!$spec_id_str) {
        // return [];
        // }
        // $key_arr = Db::name('spec_goods')->where(['goods_id' => ['in', $goods_id_arr]])->column('key');
        // $key_arr = array_unique(explode('_', implode('_', $key_arr)));
        // //Filter specification
        // $spec = Db::name('goods_spec')->where(['id' => ['in', $spec_id_str]])->select();
        //var_dump($key_arr);
        $spec_arr = [];
        // foreach ($spec as $k => $v) {
        //     $sepc_id_item = Db::name('goods_spec_item')->field('id as value,item as name')->where(['spec_id' => $v['id']])->select();
        //     //var_dump($sepc_id_item);
        //     foreach ($sepc_id_item as $k1 => $v1) {
        //         if (in_array($v1['value'], $key_arr)) {
        //             $v1['key'] = 'spec';
        //             $spec_arr[$v['name']][] = $v1;
        //         }
        //     }
        // }
        return $spec_arr;
    }


}

