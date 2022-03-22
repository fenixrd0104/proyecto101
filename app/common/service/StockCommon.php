<?php
namespace app\common\service;
use think\facade\Db;


class StockCommon{
    static public function getStockTransferShopId($shop_id){

        $shop_info =Db::name('shop_lists')->find($shop_id);
        if(!$shop_info){
            return ['code'=>0,'data'=>[],'msg'=>'shop does not exist'];
        }
        if($shop_info['pid'] == 0){
            //find his sublevel
            $shop_id_arr = Db::name('shop_lists')->where([['pid','=',$shop_id],['status','=',1],['id','<> ',$shop_id]])->column('id');
        }else{
            //All child stores of own parent store + parent store
            $shop_id_arr = Db::name('shop_lists')->where([['pid','=',$shop_info['pid']],['status','=',1],['id','<>',$shop_id]])->column('id');
            array_push($shop_id_arr,$shop_info['pid']);
        }
        return ['code'=>1,'data'=>$shop_id_arr];
    }
    static public function import(){
        $file = request()->file('file');
        $filename = $file->getRealPath();
        $handle  = fopen ($filename, "r");
        $data = ['data'=>[],'title'=>[]];
        $i=0;
        while (!feof ($handle))
        {
            $buffer  = fgets($handle, 4096);

            if($buffer){
                $username = iconv('GBK', 'UTF-8//IGNORE',trim($buffer));
                if($username){
                    list($a,$b) =explode('，',$username);
                   /* if($i ==0){
                        $data['title']['shop_name']=trim($a);
                        $data['title']['shop_id']=trim($b);
                    }else{*/
                        $data['data'][trim($a)]=trim($b);
                   // }
                }
            }
            $i++;
        }
        return $data;
    }
}

