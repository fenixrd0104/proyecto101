<?php

namespace app\admin\model;

use think\Model;
use think\facade\Db;

class Goods extends Model
{
    protected $autoWriteTimestamp = true;
    public function goodsCategory()
    {
        return $this->hasOne('GoodsCategory', 'id', 'cat_id');
    }
    public function getSpec()
    {
        return $this->hasmany('GoodsSpec', 'goods_id','id')->order('id asc');
    }
    public function getStoreCountAttr($value,$data){
        return  Db::name('goods_stock')->where(['goods_id'=>$data['id']])->sum('stock');
    }

    public static function onBeforeInsert($goods)
    {
        $goods->on_time = time(); // Added time
        $goods->last_update = time();//Last update time
        $goods->cat_id_1 && $goods->cat_id = $goods->cat_id_1;
        $goods->cat_id_2 && $goods->cat_id = $goods->cat_id_2;
        $goods->cat_id_3 && $goods->cat_id = $goods->cat_id_3;
        $goods->extend_cat_id_1 && $goods->extend_cat_id = $goods->extend_cat_id_1;
        $goods->extend_cat_id_2 && $goods->extend_cat_id = $goods->extend_cat_id_2;
        $goods->extend_cat_id_3 && $goods->extend_cat_id = $goods->extend_cat_id_3;
        $goods->spec_type = $goods->goods_type;
        $goods->keywords = str_replace('，', ',', $goods->keywords);
    }
    public static function onBeforeUpdate($goods)
    {
        $goods->last_update = time();//Last update time
        //Product Code
        $goods->cat_id_1 && $goods->cat_id = $goods->cat_id_1;
        $goods->cat_id_2 && $goods->cat_id = $goods->cat_id_2;
        $goods->cat_id_3 && $goods->cat_id = $goods->cat_id_3;
        $goods->extend_cat_id_1 && $goods->extend_cat_id = $goods->extend_cat_id_1;
        $goods->extend_cat_id_2 && $goods->extend_cat_id = $goods->extend_cat_id_2;
        $goods->extend_cat_id_3 && $goods->extend_cat_id = $goods->extend_cat_id_3;
        $goods->spec_type = $goods->goods_type;
        $goods->keywords = str_replace('，', ',', $goods->keywords);
        self::onAfterUpdateSSS($goods);

    }

    public static function onAfterInsert($goods)
    {
        //Product Code
        $goods->goods_sn = $goods->goods_sn ? $goods->goods_sn :str_pad($goods->cat_id,4,"0",STR_PAD_LEFT).str_pad($goods->id, 5, "0",STR_PAD_LEFT);
        $goods->sku = $goods->sku ? $goods->sku : $goods->goods_sn;
        //
        $spec_num = $goods->spec_num;
        
        if (isset($goods->item) && !empty($goods->item)) {
            $spec_num=0;
            foreach ($goods->item as $k => $v) {
                if(isset($v['spec_num'])){
                    if( $v['spec_num'] != 0){
                        $spec_num+=abs($v['spec_num']);
                    }
                }
                
            }
        }
        $spec_num==0?$goods->spec_num=0:$goods->spec_num=$spec_num;
        $dataArr = [
            'goods_sn' => $goods->goods_sn,
            'sku'=>$goods->sku,
            'spec_num'=>$goods->spec_num,
        ];
        if($spec_num==0){
            $dataArr['is_on_sale'] = 0;
        }
        Db::name('goods')->where('id', $goods->id)->update($dataArr);
		// product gallery
        //Whether the original image of the product exists in the gallery
        $goods_images = isset($goods->goods_images) && !empty($goods->goods_images) ? $goods->goods_images : array();
        if (!in_array($goods->original_img, $goods_images)) {
            array_unshift($goods_images, $goods->original_img);
        }
        foreach ($goods_images as $k => $v) {
            $data = ['goods_id' => $goods->id, 'image_url' => $v];
            Db::name('goods_images')->insert($data);
        }
        //Specification processing
        if (isset($goods->item) && !empty($goods->item)) {
            $i=1;
            foreach ($goods->item as $k => $v) {
				$v['market_price'] = trim($v['market_price'])!= '0.00' ? trim($v['market_price']) : $goods->market_price;//If there is no marked price, press Commodity prices go
                $v['shop_price'] = trim($v['shop_price'])!= '0.00' ? trim($v['shop_price']) : $goods->shop_price;//If there is no price, press Commodity prices go
                if(isset($v['trade_price'])){
                    $v['trade_price'] = trim($v['trade_price'])!= '0.00' ? trim($v['trade_price']) : $goods->trade_price;//If there is no marked price, press Commodity prices go
                }else{
                    $v['trade_price'] = 0;
                }
                if(isset($v['cost_price'])){
                    $v['cost_price'] = trim($v['cost_price'])!= '0.00' ? trim($v['cost_price']) : $goods->cost_price;//If there is no price, press Commodity prices go
                }else{
                    $v['cost_price'] = 0;
                }
                
                    $v['spec_num'] = trim($v['spec_num'])!= '0' ? trim($v['spec_num']) : 0;//If there is no quantity, go by the number of items
                
                
                //$v['spec_sku'] = trim($v['spec_sku']);
                $v['spec_sku'] = 0;
                if(!$v['spec_sku']){
                    $v['spec_sku']=$goods->sku.str_pad($i,4,"0",STR_PAD_LEFT);
                }
                $i++;
                $data = ['goods_id' => $goods->id, 'spec_key' => $k, 'spec_name' => $v['spec_name'],'spec_sku' => $v['spec_sku'], 'market_price' => $v['market_price'], 'shop_price' => $v['shop_price'], 'trade_price' => $v['trade_price'],'cost_price' => $v['cost_price'],'spec_num'=>$v['spec_num'] ];
                Db::name('spec_goods')->insert($data);
            }
			//modify inventory
           // $store_count < 0 ? 0 : $store_count;
           // Db::name('goods')->where('id', $goods->id)->update(['store_count' => $store_count]);
            //Product specification image processing
            if (isset($goods->item_img) && !empty($goods->item_img)) {
                $goods->item_img = array_filter($goods->item_img);
                foreach ($goods->item_img as $k => $v) {
                    $data = ['goods_id' => $goods->id, 'spec_item_id' => $k, 'src' => $v];
                    Db::name('spec_image')->insert($data);
                }
            }
        }else{
            //Specification default specification processing
            $data=[];
            $data['goods_id'] = $goods->id;
            $data['market_price'] = $goods->market_price;//If there is no marked price, go by the commodity price
            $data['shop_price'] = $goods->shop_price;//If there is no marked price, go according to the commodity price
            //$data['trade_price'] = $goods->trade_price;//If there is no marked price, go according to the commodity price
            //$data['cost_price'] = $goods->cost_price;//If there is no marked price, go according to the commodity price
            $data['spec_num'] = $goods->spec_num;//If there is no quantity, go by the number of goods
            $data['spec_sku'] = $goods->spec_sku;//If there is no quantity, go by the quantity of goods
            $data['spec_sku'] = trim($goods->sku.'0001');
            Db::name('spec_goods')->insert($data);

        }
        //property handling
        if (isset($goods->attr) && !empty($goods->attr)) {
            foreach ($goods->attr as $k => $v) {
                $data = ['goods_id' => $goods->id, 'attr_id' => $k, 'attr_value' => $v];
                Db::name('goods_attr')->insert($data);
            }
        }
    }
    //It was originally operated after the update, and now it is judged before the update
    public static function onAfterUpdateSSS($goods)
    {
        //Product Code
        $goods->goods_sn = $goods->goods_sn ? $goods->goods_sn :str_pad($goods->cat_id,4,"0",STR_PAD_LEFT).str_pad($goods->id, 5, "0",STR_PAD_LEFT);
        $goods->sku = $goods->sku ? $goods->sku : $goods->goods_sn;
        $spec_num = $goods->spec_num;
        if (isset($goods->item) && !empty($goods->item)) {
            $spec_num=0;
            foreach ($goods->item as $k => $v) {
                if(isset($v['spec_num'])){
                    if( $v['spec_num'] != 0){
                        $spec_num+=abs($v['spec_num']);
                    }
                }
            }
        }

        $spec_num==0?$goods->spec_num=0:$goods->spec_num=$spec_num;
        $dataArr = [
            'goods_sn' => $goods->goods_sn,
            'sku'=>$goods->sku,
            'spec_num'=>$goods->spec_num,
        ];
        if($spec_num==0){
            $dataArr['is_on_sale'] = 0;
        }
        Db::name('goods')->where('id', $goods->id)->update($dataArr);
        

        // product gallery
        // delete all galleries first
        Db::name('goods_images')->where('goods_id', $goods->id)->delete();
        //Whether the original image of the product exists in the gallery
        $goods_images = isset($goods->goods_images) && !empty($goods->goods_images) ? $goods->goods_images : array();
        if (!in_array($goods->original_img, $goods_images)) {
            array_unshift($goods_images, $goods->original_img);
        }
        foreach ($goods_images as $k => $v) {
            $data = ['goods_id' => $goods->id, 'image_url' => $v];
            Db::name('goods_images')->insert($data);
        }

        //Specification processing
        //First take out all the specifications, the default one is no specifications
        $spec_key = Db::name('spec_goods')->where('goods_id', $goods->id)->column('spec_key');
        
        if(!current($spec_key) && isset($goods->item) && !empty($goods->item)){
            //throw new \think\Exception('Specifications cannot be deleted after the product is added', 10006);
        }
        Db::name('spec_goods')->where('goods_id', $goods->id)->delete();
        //Specification processing

        if (isset($goods->item) && !empty($goods->item)) {
            $i=1;
            foreach ($goods->item as $k => $v) {
				$v['market_price'] = trim($v['market_price'])!= '0.00' ? trim($v['market_price']) : $goods->market_price;//If there is no marked price, press Commodity prices go
                $v['shop_price'] = trim($v['shop_price'])!= '0.00' ? trim($v['shop_price']) : $goods->shop_price;//If there is no price, press Commodity prices go
                if(isset($v['trade_price'])){
                    $v['trade_price'] = trim($v['trade_price'])!= '0.00' ? trim($v['trade_price']) : $goods->trade_price;//If there is no marked price, press Commodity prices go
                }else{
                    $v['trade_price'] = 0;
                }
                if(isset($v['cost_price'])){
                    $v['cost_price'] = trim($v['cost_price'])!= '0.00' ? trim($v['cost_price']) : $goods->cost_price;//If there is no price, press Commodity prices go
                }else{
                    $v['cost_price'] = 0;
                }
                
                    $v['spec_num'] = trim($v['spec_num'])!= '0' ? trim($v['spec_num']) : 0;//If there is no quantity, go by the number of items
                
                
                //$v['spec_sku'] = trim($v['spec_sku']);
                $v['spec_sku'] = 0;
                if(!$v['spec_sku']){
                    $v['spec_sku']=$goods->sku.str_pad($i,4,"0",STR_PAD_LEFT);
                }
                $i++;
                $data = ['goods_id' => $goods->id, 'spec_key' => $k, 'spec_name' => $v['spec_name'],'spec_sku' => $v['spec_sku'], 'market_price' => $v['market_price'], 'shop_price' => $v['shop_price'], 'trade_price' => $v['trade_price'],'cost_price' => $v['cost_price'],'spec_num'=>$v['spec_num'] ];
                Db::name('spec_goods')->insert($data);

            //After modifying the product, the price of the product in the shopping cart is also modified
                Db::name('cart')->where(['goods_id' => $goods->id, 'spec_key' => $k, 'prom_type' => 0])->update([
                    'market_price' => $v['market_price'], //market price
                    'goods_price' => $v['shop_price'], // our shop price
                    'member_goods_price' => $v['shop_price'], // member discount price
                ]);
            }
            //modify inventory
           // $store_count < 0 ? 0 : $store_count;
           // Db::name('goods')->where('id', $goods->id)->update(['store_count' => $store_count]);
            //Product specification image processing
            if (isset($goods->item_img) && !empty($goods->item_img)) {
                $goods->item_img = array_filter($goods->item_img);
                foreach ($goods->item_img as $k => $v) {
                    $data = ['goods_id' => $goods->id, 'spec_item_id' => $k, 'src' => $v];
                    Db::name('spec_image')->insert($data);
                }
            }
        }else{
        //Specification default specification processing
            $data=[];
            $data['goods_id'] = $goods->id;
            $data['market_price'] = $goods->market_price;//If there is no marked price, go by the commodity price
            $data['shop_price'] = $goods->shop_price;//If there is no marked price, go according to the commodity price
            //$data['trade_price'] = $goods->trade_price;//If there is no marked price, go according to the commodity price
            //$data['cost_price'] = $goods->cost_price;//If there is no marked price, go according to the commodity price
            $data['spec_num'] = $goods->spec_num;//If there is no quantity, go by the number of goods
            $data['spec_sku'] = $goods->spec_sku;//If there is no quantity, go by the quantity of goods
            $data['spec_sku'] = trim($goods->sku.'0001');
            Db::name('spec_goods')->insert($data);

            // cart price change
            Db::name('cart')->where(['goods_id' => $goods->id, 'spec_key' => ''])->update([
                'market_price' => $goods->market_price, // market price
                'goods_price' => $goods->shop_price, // our shop price
                'member_goods_price' => $goods->shop_price // member discount price
            ]);

        }
        
        //previously added processing
     //    				if(current($spec_key)){
     //      		  $spec_key_arr_count = count(explode('_',current($spec_key)));


     //       		 if (isset($goods->item) && !empty($goods->item)) {
     //            foreach ($goods->item as $k => $v) {
     //                // if($spec_key_arr_count != count(explode('_',$k))){
     //                //     throw new \think\Exception('After the product is added, it is forbidden to delete the specifications', 10006);
     //                // }
                    
     // 				$v['market_price'] = trim($v['market_price'])!= '0.00' ? trim($v['market_price']) : $goods->market_price;//If there is no marked price, go by commodity price
     // 				$v['shop_price'] = trim($v['shop_price'])!= '0.00' ? trim($v['shop_price']) : $goods->shop_price;//If there is no price, go by commodity price
     // 				if(!isset($v['trade_price'])){
                    // $v['trade_price'] = 0;
                    // }
                    // $v['trade_price'] = trim($v['trade_price'])!= '0.00' ? trim($v['trade_price']) : $goods->trade_price;//If there is no marked price, go by commodity price
     // 				if(!isset($v['cost_price'])){
     //				 $v['cost_price'] = 0;
     // }
                    // $v['cost_price'] = trim($v['cost_price'])!= '0.00' ? trim($v['cost_price']) : $goods->cost_price;//If there is no price, go by commodity price
     //                $v['spec_num'] = trim($v['spec_num'])!= '0' ? trim($v['spec_num']) : 0;//If there is no quantity, go by the number of items
                    
                    // $v['spec_sku'] = 0;
                    // $v['spec_sku'] = trim($v['spec_sku']);
     //                $i=1;
     //                if(!$v['spec_sku']){
     //                    $v['spec_sku']=$goods->sku.str_pad($i,4,"0",STR_PAD_LEFT);
     //                }
     //                $i++;

     //                $data = ['goods_id' => $goods->id, 'spec_key' => $k, 'spec_name' => $v['spec_name'], 'spec_sku' => $v['spec_sku'],'market_price' => $v['market_price'], 'shop_price' => $v['shop_price'], 'trade_price' => $v['trade_price'], 'cost_price' => $v['cost_price'],'spec_num'=>$v['spec_num']];

     // 			//Then loop over existing specifications to modify // everything else is discarded

     // 			if(in_array($k,$spec_key)){
     // 			Db::name('spec_goods')->where(['goods_id'=>$goods->id,'spec_key'=>$k])->update($data);
     // 			//After modifying the product, the price of the product in the shopping cart is also modified
     // 			Db::name('cart')->where(['goods_id' => $goods->id, 'spec_key' => $k, 'prom_type' => 0])->update([
     // 			'market_price' => $v['market_price'], //market price
     // 			'goods_price' => $v['shop_price'], // our shop price
     // 			'member_goods_price' => $v['shop_price'], // member discount price
     // 			]);
     // 			}else{
     // 			Db::name('spec_goods')->insert($data);

     // 			}
     // 			// Db::name('spec_goods')->insert($data);

     // 			}
     // 			//Modify inventory
     // 			// $store_count < 0 ? 0 : $store_count;
     // 			// Db::name('goods')->where('id', $goods->id)->update(['store_count' => $store_count]);
     // 			//Product specification image processing
     // 			//First delete all images
     //            Db::name('spec_image')->where('goods_id', $goods->id)->delete();
     //            if (isset($goods->item_img) && !empty($goods->item_img)) {
     //                $goods->item_img = array_filter($goods->item_img);
     //                foreach ($goods->item_img as $k => $v) {
     //                    $data = ['goods_id' => $goods->id, 'spec_item_id' => $k, 'src' => $v];
     //                    Db::name('spec_image')->insert($data);
     //                }
     //            }
     //        }

     //    }else{
     //        //Specification Default Specification Handling
     //        $data=[];
     //        $data['market_price'] =  $goods->market_price;
     //        $data['shop_price'] =  $goods->shop_price;
     //        $data['trade_price'] =  $goods->trade_price;
     //        $data['cost_price'] =  $goods->cost_price;
     //        $data['spec_num'] = $goods->spec_num;
     //        $data['spec_sku'] =  $goods->spec_sku.'0001';
     //        $data['goods_id'] = $goods->id;
     //        $data['spec_key'] = '';
     //        $data['spec_name'] = '';
     //        Db::name('spec_goods')->where(['goods_id'=>$goods->id])->update($data);

     // //Cart price change
     // Db::name('cart')->where(['goods_id' => $goods->id, 'spec_key' => ''])->update([
     // 'market_price' => $goods->market_price, // market price
     // 'goods_price' => $goods->shop_price, // shop price
     // 'member_goods_price' => $goods->shop_price // member discount price
     //        ]);
     //    }
     //property handling
        // first delete all properties
        Db::name('goods_attr')->where('goods_id', $goods->id)->delete();
        if (isset($goods->attr) && !empty($goods->attr)) {
            foreach ($goods->attr as $k => $v) {
                $data = ['goods_id' => $goods->id, 'attr_id' => $k, 'attr_value' => $v];
                Db::name('goods_attr')->insert($data);
            }
        }

    }
    public static function onBeforeDelete($goods)
    {
         // delete all galleries first
        Db::name('goods_images')->where('goods_id', $goods->id)->delete();
        // then delete all specifications
        Db::name('spec_goods')->where('goods_id', $goods->id)->delete();
        // delete all images
        Db::name('spec_image')->where('goods_id', $goods->id)->delete();
        // delete all properties
        Db::name('goods_attr')->where('goods_id', $goods->id)->delete();
    }
    public function getSpecInput($goods_id = 0, $spec_arr)
    {

        if (!$spec_arr) {
            return '';
        }

        foreach ($spec_arr as $k => $v)
        {
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key =>$val)
        {
            $spec_arr2[$key] = $spec_arr[$key];
        }
        $spec_arr = $spec_arr2;
        unset($spec_arr2);
        //Get the column name of the specification
        $clo_name = array_keys($spec_arr);
        //Get the Cartesian product of the specification
        $spec_arr = combineDika($spec_arr);
        //Specification
        $spec = Db::name('GoodsSpec')->order('order asc')->column('name','id');
        $base_sort = array_keys($spec);
        //Specification item
        $specItem = Db::name('GoodsSpecItem')->column('item,spec_id','id');
        $keySpecGoods = Db::name('spec_goods')->where('goods_id', $goods_id)->column('spec_name,spec_sku,market_price,shop_price,trade_price,cost_price,spec_num','spec_key');// Specification item
        //var_dump($keySpecGoods);die;
        //Start constructing the form
        //construct header
        $html = '<tr class="long-td">';
        foreach ($clo_name as $v) {
            $html .= '<td style="text-align:left"><b>'.$spec[$v].'</b></td>';
        }
       //$html .= '<td style="text-align:left"><b>Barcode</b></td><td style="text-align:left"><b>Retail Price</b> b></td><td style="text-align:left"><b>Member price</b></td><td style="text-align:left"><b>Wholesale price</b> b></td><td style="text-align:left"><b>Purchase price</b></td><td style="text-align:left"><b>Quantity</b ></td></tr>';
        $html .= '<td style="text-align:left"><b>Retail Price</b></td><td style="text-align:left"><b>Member Price</b ></td><td style="text-align:left"><b>Quantity</b></td></tr>';
        foreach ($spec_arr as $k => $v) {

            $html .= '<tr class="long-td secondfloor">';
            $_v = explode('_', $v);
            foreach($_v as $k1 => $v1) {
                $html .= '<td>' . $specItem[$v1]['item'] . '</td>';
            }
            //Customize a wave of sorting
            usort($_v, function($s1, $s2)use($base_sort, $specItem){
                $s1_index = array_search($specItem[$s1]['spec_id'], $base_sort);
                $s2_index = array_search($specItem[$s2]['spec_id'], $base_sort);
                return $s1_index < $s2_index ? -1 : 1;
            });
            $key_name = '';
            foreach ($_v as $v1) {
                $key_name .= $spec[$specItem[$v1]['spec_id']] . ':' . $specItem[$v1]['item'].' ';
            }
            //Get price, inventory, sku, key_name
            //$price = $keySpecGoods[implode('_', $_v)]
            $_v = implode('_', $_v);
            $market_price       = isset($keySpecGoods[$_v]) ?  $keySpecGoods[$_v]['market_price'] : '0.00';
            $shop_price       = isset($keySpecGoods[$_v]) ?  $keySpecGoods[$_v]['shop_price'] : '0.00';
            $trade_price       = isset($keySpecGoods[$_v]) ?  $keySpecGoods[$_v]['trade_price'] : '0.00';
            
            $cost_price       = isset($keySpecGoods[$_v]) ?  $keySpecGoods[$_v]['cost_price'] : '0.00';
            $sku         = isset($keySpecGoods[$_v]) ?  $keySpecGoods[$_v]['spec_sku'] : '';
            $spec_num       = isset($keySpecGoods[$_v]) ?  $keySpecGoods[$_v]['spec_num'] : '0';
            //$html .= '<td><input type="text" class="form-control" name="item['.$_v.'][spec_sku]" value ="'.$sku.'"></td>';
            $html .= '<td><input type="text" class="form-control" name="item['.$_v.'][market_price]" value ="'.$market_price.'"></td>';
            $html .= '<td><input type="text" class="form-control" name="item['.$_v.'][shop_price]" value ="'.$shop_price.'"></td>';
            //$html .= '<td><input type="text" class="form-control" name="item['.$_v.'][trade_price]"  value="'.$trade_price.'"></td>';
            //$html .= '<td><input type="text" class="form-control" name="item['.$_v.'][cost_price]" value="'.$cost_price.'"></td>';
            $html .= '<td><input type="text" class="form-control" name="item['.$_v.'][spec_num]" value="'.$spec_num.'"></td>';
            $html .= '<td><input type="hidden" class="form-control" name="item['.$_v.'][spec_name]" value="'.trim($key_name).'"></td>';
            $html .= '</tr>';
        }
        return $html;
    }
}

