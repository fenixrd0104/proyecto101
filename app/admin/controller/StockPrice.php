<?php

namespace app\admin\controller;
use app\admin\model\GoodsStock;
use app\admin\model\Goods;
use app\admin\model\ShopListsModel;
use app\admin\model\SpecGoodsModel;
use app\admin\model\StockInventoryListsModel;
use app\admin\model\StockInventoryModel;
use app\admin\model\StockPriceListsModel;
use app\admin\model\StockPriceModel;
use think\Exception;
use think\facade\Db;

class StockPrice extends Base
{



    //list
    public function index(){
        $StockPriceModel = new StockPriceModel();
        $map=[];
        if($this->shopId){
            $map[]=['shop_id','=',$this->shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        $status = input('get.status','');


        if($keyWords != ''){
            $map[]=['think_stock_price.id|think_shop_lists.name', 'like', "%".$keyWords."%"];
        }
        if($startTime){
            $map[]=['think_stock_price.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_stock_price.create_time','<',strtotime($endTime)];
        }
        if($status != ''){
            $map[]=['think_stock_price.status','=',$status];
        }




        $res = $StockPriceModel->getPaginate($map);
        $lists = $res->toArray();
        return json(['code'=>1,'data'=>['count'=>$lists['total'],'lists'=>$lists['data'],'status'=>$StockPriceModel->orderStatus],'msg'=>'']);
    }


    //New inventory
    public function crate(){
       $StockInventoryModel = new StockPriceModel();
       $shop_id = $this->shopId;
       if(!$shop_id){
           $shop_id=config('config.shop_default_manage');
       }
       $id = $StockInventoryModel->createInventory($shop_id,$this->userName,$this->userId);
       if(!$id){
		return json(['code'=>0,'data'=>[],'msg'=>'create failed']);
       }
       return json(['code'=>1,'data'=>['id'=>$id],'msg'=>'created successfully']);
    }
    //Inventory list
    public function lists(){
        if($this->request->isPost()){
            $order_id= input('post.price_id');
            $post= input('post.');
            $post_goods= input('post.data');
            $goods=[];
            $shop_id = $this->shopId;
            if(!$shop_id){
                $shop_id=config('config.shop_default_manage');
            }
            $StockPriceModel = new StockPriceModel();
            $map=['id'=>$order_id];
            $map['shop_id']=$shop_id;
            $order_info = $StockPriceModel->where($map)->find();
            if(!$order_info ||$order_info->getData('status') != 1){
                return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be operated']);
            }
            if($post_goods){
                $SpecGoodsModel= new SpecGoodsModel();
                $GoodsStock = new GoodsStock();
                foreach ($post_goods['spec_id'] as $k => $v){
                    $data=[];
					// Find this based on the submitted goods_spec_id
                    $SpecGoods_info = $SpecGoodsModel->where(['spec_id'=>$v])->find();
                    $goods_id=$SpecGoods_info['goods_id'];
                    //find current stock
                    $stock_info = $GoodsStock->where(['shop_id'=>$shop_id,'spec_id'=>$v])->find();
                    if($stock_info){
                        $stock=$stock_info['stock'];
                        $market_price=$stock_info['market_price'];
                        $shop_price=$stock_info['shop_price'];
                        $trade_price=$stock_info['trade_price'];
                        $cost_price=$stock_info['cost_price'];
                    }else{
                        $stock =0;
                        $market_price=$SpecGoods_info['market_price'];
                        $shop_price=$SpecGoods_info['shop_price'];
                        $trade_price=$SpecGoods_info['trade_price'];
                        $cost_price=$SpecGoods_info['cost_price'];

                    }
                  /*  if($post_goods['inventory_stock'][$k] < 0){
                        return json(['code'=>0,'data'=>[],'msg'=>'Inventory inventory cannot be negative']);
                    }*/

                    $data['price_id']=$order_id;
                    $data['spec_id']=$v;
                    $data['goods_id']=$goods_id;
                    $data['stock']=$stock;

                    $data['market_price']=$market_price;
                    $data['shop_price']=$shop_price;
                    $data['trade_price']=$trade_price;
                    $data['cost_price']=$cost_price;


                    $data['new_market_price']=$post_goods['new_market_price'][$k];
                    $data['new_shop_price']=$post_goods['new_shop_price'][$k];
                    $data['new_trade_price']=$post_goods['new_trade_price'][$k];
                    $data['new_cost_price']=$post_goods['new_cost_price'][$k];

                    $data['market_price_diff']=($post_goods['new_market_price'][$k]-$market_price)*$stock;
                    $data['shop_price_diff']=($post_goods['new_shop_price'][$k]-$shop_price)*$stock;
                    $data['trade_price_diff']=($post_goods['new_trade_price'][$k]-$trade_price)*$stock;
                    $data['cost_price_diff']=($post_goods['new_cost_price'][$k]-$cost_price)*$stock;

                    $data['remarks']=$post_goods['remarks'][$k];
                    $data['update_time']=time();
                    $data['create_time']=time();
                    $goods[]=$data;
                }
            }
            Db::startTrans();
            try {
                $order_info->save($post);
                $StockPriceListsModel = new StockPriceListsModel();
			// first delete and save
                $StockPriceListsModel->where(['price_id'=>$order_id])->delete();
                $StockPriceListsModel->saveAll($goods);
                Db::commit();
            } catch (Exception $e) {
                // validation failed output error message
                Db::rollback();
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            //add log
           // PurchaseOrderLogModel::operate($this->userId,$order_id,$this->userName." Modified purchase order:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'Successfully modified']);
        }

        $order_id= input('get.price_id');
        $StockPriceModel = new StockPriceModel();
        $map=['id'=>$order_id];
        if($this->shopId){
            $map['shop_id']=$this->shopId;
        }
        $order_info = $StockPriceModel->where($map)->find();

        if(!$order_info){
            return json(['code'=>0,'data'=>[],'msg'=>'The information was not found']);
        }
        $StockPriceListsModel = new StockPriceListsModel();
        $lists = $StockPriceListsModel->getLists(['price_id'=>$order_id]);

        $order_info->shop_name=$order_info->getShopName();

        return json(['code'=>1,'data'=>['info'=>$order_info,'lists'=>$lists],'msg'=>'']);

    }
    //Inventory completed
    public function complete(){
        $order_id= input('price_id');
        $StockPriceModel = new StockPriceModel();
        $map=['id'=>$order_id];
        if($this->shopId){
            $map['shop_id']=$this->shopId;
        }
        $order_info = $StockPriceModel->where($map)->find();
        if(!$order_info || $order_info->getData('status') != 1){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be operated']);
        }
        $order_info->status=2;
        Db::startTrans();
        try {
            if($order_info->save()){
                $StockPriceListsModel = new StockPriceListsModel();
                $GoodsStock = new GoodsStock();
				//modify the order list
                $all_stock = $StockPriceListsModel->where(['price_id'=>$order_id])->select();
                foreach ($all_stock as $v){
                    $GoodsStock->priceStock($this->userId,$order_id,$order_info->shop_id,$v);
                }
                // commit the transaction
                Db::commit();
                return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
            }
            Db::rollback();
            return json(['code'=>0,'data'=>[],'msg'=>'Completion failed']);
        } catch (\Exception $e) {
            // rollback transaction
            Db::rollback();
          return json(['code'=>0,'data'=>[],'msg'=>$e->getMessage()]);
        }

    }
    //Cancel
    public function cancel(){
        $order_id= input('price_id');
        $StockPriceModel = new StockPriceModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['shop_id']=$this->shopId;
        }
        $order_info = $StockPriceModel->where($map)->find();

        if(!$order_info || $order_info->getData('status') != 1 ){
		return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be cancelled']);
        }
        $order_info->status=3;
       if($order_info->save()){ //Add log
         // PurchaseOrderLogModel::operate($this->userId,$order_id,$this->userName." canceled purchase order:".$order_id);
           return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }

		//Find products according to spec_id
    public function getSpecStock($spec_id){
        $GoodsStock = new GoodsStock();
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        // Check first if it exists
        $stock = $GoodsStock->getOneStockDetails($shop_id,$spec_id);
        // Update if it exists
        if($stock){
            return json(['code'=>1,'data'=>$stock,'msg'=>'']);

        }else{
            $SpecGoodsModel =  new SpecGoodsModel();
            $stock =  $SpecGoodsModel->getOneDetails($spec_id);
            $stock['stock']=0;
            return json(['code'=>1,'data'=>$stock,'msg'=>'']);
        }
    }
    public function searchByGoodsSku($spec_sku){
        $GoodsStock =  new GoodsStock();
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
		// Check first if it exists
        $stock = $GoodsStock->getOneStockDetailsBySku($shop_id,$spec_sku);
        // Update if it exists
        if($stock){
            return json(['code'=>1,'data'=>$stock,'msg'=>'']);

        }else{
            $SpecGoodsModel =  new SpecGoodsModel();
            $stock =  $SpecGoodsModel->getOneDetailsBySku($spec_sku);
            $stock['stock']=0;
            return json(['code'=>1,'data'=>$stock,'msg'=>'']);
        }
    }

}