<?php

namespace app\common\model;
use think\facade\Db;
use think\Model;

class Industry extends Model
{
    // Enable automatic writing of timestamp fields
    protected $autoWriteTimestamp = true;

    public static function getCateLists(){
        $arr = self::where(['status'=>1])->order('sort asc')->select()->toArray();
        $list = [];
        foreach ($arr as $k => $v) {
            if ($v['parent_id'] == 0) {
                foreach ($arr as $k1 => $v1) {
                    if ($v1['parent_id'] == $v['id']) {
                      foreach ($arr as $k2 => $v2) {
                            if ($v2['parent_id'] ==  $v1['id']) {
                                $v1['children'][] = $v2;
                            }
                        }
                        $v['children'][] = $v1;
                    }
                }
                $list[] = $v;
            }
        }

        return $list;
    }

}

