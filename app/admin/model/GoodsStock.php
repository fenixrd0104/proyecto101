<?php

namespace app\admin\model;
use think\Exception;
use think\facade\Db;
use think\Model;

class GoodsStock extends Model
{
    //type 1 store purchase 2 mall allocation fixed point stick 3 mall order cancellation store 4 store return 5 inventory 6 cost price adjustment 7 delivery 8 adjustment delivery 9 cashier order 10 cashier cancel order 11 cashier return 21 inventory Report overflow 22 inventory report loss
    protected $autoWriteTimestamp = true;
    //Purchasing warehousing plus inventory, the inventory table can be increased if there is no more
    public function addStock($uid,$act,$shop_id,$goods_id,$spec_id,$num,$price,$type=1,$rem ='Purchase into stock'){
        $now_stock = $this->getNowStock($shop_id,$spec_id);
        // Check first if it exists
        $stock = $this->where(['shop_id'=>$shop_id,'spec_id'=>$spec_id])->find();
        // Update if it exists
        if($stock){
            //The cost of goods is automatically weighted and averaged
            if($stock->cost_price !=$price){
                //View previous purchase quantity and current price
              $count_num =  Db::name('purchase_receipt_lists')->where(['shop_id'=>$shop_id,'spec_id'=>$spec_id,'receipt_status'=>1])->sum('receipt_num');
                $new_cost_price = (($count_num * $stock->cost_price)+($num*$price))/($num+$count_num);
                //add log
                GoodsPriceLog::operate($uid,$goods_id,$spec_id,$shop_id,$stock['market_price'],$stock['market_price'],$stock['shop_price'],$stock['shop_price'],$stock['trade_price'],$stock['trade_price'],$stock->cost_price,$new_cost_price,$type,$act,$rem);

               $stock->cost_price =$new_cost_price;
            }
            $stock->stock=$stock->stock+$num;
            $stock->save();

            // if it does not exist, add it directly
        }else{
            // check the price first
            $spec_goods = Db::name('spec_goods')->find($spec_id);
            $d =[
              'shop_id'=>  $shop_id,
              'goods_id'=>  $goods_id,
              'spec_id'=>  $spec_id,
              'stock'=>  $num,
              'market_price'=>  $spec_goods['market_price'],
              'shop_price'=>  $spec_goods['shop_price'],
              'trade_price'=>  $spec_goods['trade_price'],
              'cost_price'=>  $price,
                'update_time'=>  time(),
                'create_time'=>  time(),
            ];
            $id = $this->insert($d,true);
            GoodsPriceLog::operate($uid,$goods_id,$spec_id,$shop_id,$spec_goods['market_price'],$spec_goods['market_price'],$spec_goods['shop_price'],$spec_goods['shop_price'],$spec_goods['trade_price'],$spec_goods['trade_price'],$price,$price,$type,$act,$rem);
        }
        return GoodsStockLog::operate($uid,$goods_id,$spec_id,$shop_id,$type,$num,$price,$act,$rem,$now_stock['shop_stock'],$now_stock['system_stock']);
  }
        //Reduce inventory The inventory table must have
    public function decStock($uid,$goods_id,$spec_id,$shop_id,$num,$price,$act,$type=2,$rem = 'Mall order allocation'){
        if($num <= 0){
            throw new Exception('Quantity configuration error', 500);
        }
        $stock = $this->where(['shop_id'=>$shop_id,'spec_id'=>$spec_id])->find();
        // stock does not exist
        if(!$stock){
            throw new Exception('Store goods:'.$goods_id.' out of stock', 500);
        }
        //Inventory shortage
        if($stock->stock < $num){
            throw new Exception('Store goods:'.$goods_id.'Insufficient stock', 501);
        }
        $stock->stock=$stock->stock-$num;
        $now_stock = $this->getNowStock($shop_id,$spec_id);
        if($stock->save()){
            return GoodsStockLog::operate($uid,$goods_id,$spec_id,$shop_id,$type,-$num,$price,$act,$rem,$now_stock['shop_stock'],$now_stock['system_stock']);
        }else{
            throw new Exception('Failed to modify store inventory', 502);
        }

    }
       //Add inventory The inventory table must have
    public function incStock($uid,$goods_id,$spec_id,$shop_id,$num,$price,$act,$type=3,$rem = 'Unassign order'){
        $stock = $this->where(['shop_id'=>$shop_id,'spec_id'=>$spec_id])->find();
        // stock does not exist
        if(!$stock){
            throw new Exception('The store does not have this specification item', 503);
        }
        $stock->stock=$stock->stock+$num;
        $now_stock = $this->getNowStock($shop_id,$spec_id);
        if($stock->save()){
            return GoodsStockLog::operate($uid,$goods_id,$spec_id,$shop_id,$type,$num,$price,$act,$rem,$now_stock['shop_stock'],$now_stock['system_stock']);
        }else{
            throw new Exception('Failed to modify store inventory', 504);
        }

    }

      // Inventory and warehousing, the inventory table can be added if there is no more
    public function inventoryStock($uid,$act,$shop_id,$data){
        if($data['difference'] == 0){
            return true;
        }
        $spec_id = $data['spec_id'];
        $now_stock = $this->getNowStock($shop_id,$spec_id);
        // Check first if it exists
        $stock = $this->where(['shop_id'=>$shop_id,'spec_id'=>$spec_id])->find();
        // Update if it exists
        if($stock){
            if($stock->stock+$data['difference'] < 0){
                throw new Exception('Inventory cannot be negative:'.$data['goods_id'], 500);
            }
            $stock->stock=$stock->stock+$data['difference'];
            $stock->save();
            $price=$stock->cost_price;

        }else{
            // check the price first
            $spec_goods = Db::name('spec_goods')->find($data['spec_id']);
            $d =[
                'shop_id'=>  $shop_id,
                'goods_id'=>  $data['goods_id'],
                'spec_id'=>  $data['spec_id'],
                'stock'=>  $data['difference'],
                'market_price'=>  $spec_goods['market_price'],
                'shop_price'=>  $spec_goods['shop_price'],
                'trade_price'=>  $spec_goods['trade_price'],
                'cost_price'=>  $spec_goods['cost_price'],
                'update_time'=>  time(),
                'create_time'=>  time(),
            ];
            $price=$spec_goods['cost_price'];
            $id = $this->insert($d,true);
            GoodsPriceLog::operate($uid,$data['goods_id'],$spec_id,$shop_id,$spec_goods['market_price'],$spec_goods['market_price'],$spec_goods['shop_price'],$spec_goods['shop_price'],$spec_goods['trade_price'],$spec_goods['trade_price'],$spec_goods['cost_price'],$spec_goods['cost_price'],5,$id,'inventory check');
        }
        $type = $data['difference'] >0 ? 21:22;
        $remark = $data['difference'] >0 ? 'Counting report overflow': 'Counting report loss';
        return GoodsStockLog::operate($uid,$data['goods_id'],$data['spec_id'],$shop_id,$type,$data['difference'],$price,$act,$remark,$now_stock['shop_stock'],$now_stock['system_stock']);



    }

    //Inventory adjustment
    public function priceStock($uid,$act,$shop_id,$data){
        if($data['new_market_price'] == $data['market_price'] && $data['new_shop_price'] == $data['shop_price']&& $data['new_trade_price'] == $data['trade_price']&& $data['new_cost_price'] == $data['cost_price']){
            return true;
        }
        if($data['new_market_price'] < 0 || $data['new_shop_price']< 0 || $data['new_trade_price']< 0 || $data['new_cost_price']< 0){
            throw new Exception('Price cannot be negative:'.$data['goods_id'], 500);
        }
        $spec_id = $data['spec_id'];
       // Check first if it exists
        $stock = $this->where(['shop_id'=>$shop_id,'spec_id'=>$spec_id])->find();

        // Update if it exists
        if($stock){
            $market_price= $stock->market_price;
            $shop_price= $stock->shop_price;
            $trade_price= $stock->trade_price;
            $cost_price= $stock->cost_price;
            $stock->market_price=$data['new_market_price'];
            $stock->shop_price=$data['new_shop_price'];
            $stock->trade_price=$data['new_trade_price'];
            $stock->cost_price=$data['new_cost_price'];
            $stock->save();
         return   GoodsPriceLog::operate($uid,$data['goods_id'],$spec_id,$shop_id,$market_price,$data['new_market_price'],$shop_price,$data['new_shop_price'],$trade_price,$data['new_trade_price'],$cost_price,$data['new_cost_price'],6,$act,'cost adjustment');
        }else{
            //Check the price first
            $spec_goods = Db::name('spec_goods')->find($data['spec_id']);
            if(!$spec_goods){
                throw new Exception('Specifications that do not existID:'.$data['goods_id'], 500);
            }
            $d =[
                'shop_id'=>  $shop_id,
                'goods_id'=>  $data['goods_id'],
                'spec_id'=>  $data['spec_id'],
                'stock'=>  0,
                'market_price'=>  $data['new_market_price'],
                'shop_price'=>  $data['new_shop_price'],
                'trade_price'=>  $data['new_trade_price'],
                'cost_price'=>  $data['new_cost_price'],
                'update_time'=>  time(),
                'create_time'=>  time(),
            ];
            $this->insert($d);
        return    GoodsPriceLog::operate($uid,$data['goods_id'],$spec_id,$shop_id,$spec_goods['market_price'],$data['new_market_price'],$spec_goods['shop_price'],$data['new_shop_price'],$spec_goods['trade_price'],$data['new_trade_price'],$spec_goods['cost_price'],$data['new_cost_price'],6,$act,'cost adjustment');
        }
       // return GoodsStockLog::operate($uid,$data['goods_id'],$data['spec_id'],$shop_id,6,0,$act,'cost adjustment',$now_stock['shop_stock'],$now_stock['system_stock']);



    }

   //Query a store inventory details according to $spec_id
   public function getOneStockDetails($shop_id, $spec_id){
        return $this->field('think_goods_stock.*,think_spec_goods.*,think_goods.goods_name,goods_sn,give_integral,exchange_integral')->join('think_spec_goods','think_spec_goods.spec_id=think_goods_stock.spec_id','left')- >join('think_goods','think_goods.id=think_spec_goods.goods_id','left')->where(['think_goods_stock.shop_id'=>$shop_id,'think_goods_stock.spec_id'=>$spec_id])->find ();
    }
  //Query a store inventory details according to $spec_sku
    public function getOneStockDetailsBySku($shop_id,$spec_sku){
        return $this->field('think_goods_stock.*,think_spec_goods.*,think_goods.goods_name,goods_sn,give_integral,exchange_integral')->join('think_spec_goods','think_spec_goods.spec_id=think_goods_stock.spec_id','left')->join('think_goods','think_goods.id=think_spec_goods.goods_id','left')->where(['think_goods_stock.shop_id'=>$shop_id,'think_spec_goods.spec_sku'=>$spec_sku])->find();
    }
   public function getPaginate($map){
       $limits = input('get.limit',15);
       return $this->field('goods_name,goods_sn,original_img,supplier_id,think_goods_stock.shop_id,think_goods_stock.*,think_spec_goods
.spec_name,think_spec_goods.spec_sku,think_shop_lists.name as shop_name')->join('think_goods','think_goods.id=think_goods_stock.goods_id','left')->join('think_spec_goods
','think_spec_goods.spec_id=think_goods_stock.spec_id','left')->join('think_shop_lists','think_goods_stock.shop_id=think_shop_lists.id','left')->where($map)->order('think_goods_stock.goods_id desc')->paginate($limits);
   }

    public function getSumArr($map){
        return $this->field('sum(think_goods_stock.cost_price) cost_price,sum(stock) stock')->join('think_goods','think_goods.id=think_goods_stock.goods_id','left')->join('think_spec_goods
','think_spec_goods.spec_id=think_goods_stock.spec_id','left')->join('think_shop_lists','think_goods_stock.shop_id=think_shop_lists.id','left')->where($map)->find();

    }

   private function getNowStock($shop_id,$spec_id){
       $shop_stock = Db::name('goods_stock')->where(['shop_id'=>$shop_id,'spec_id'=>$spec_id])->sum('stock');
       $system_stock=Db::name('goods_stock')->where(['spec_id'=>$spec_id])->sum('stock');
       return [
           'shop_stock'=>$shop_stock,
           'system_stock'=>$system_stock
       ];
   }




}

