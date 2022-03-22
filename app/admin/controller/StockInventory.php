<?php

namespace app\admin\controller;
use app\admin\model\GoodsPriceLog;
use app\admin\model\GoodsStock;
use app\admin\model\GoodsStockLog;
use app\admin\model\ShopListsModel;
use app\admin\model\SpecGoodsModel;
use app\admin\model\StockInventoryListsModel;
use app\admin\model\StockInventoryModel;
use app\common\service\StockCommon;
use think\Exception;
use think\facade\Db;

class StockInventory extends Base
{
		//list
    public function index(){
        $StockInventoryModel = new StockInventoryModel();
        $map=[];
        if($this->shopId){
            $map[]=['shop_id','=',$this->shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        $status = input('get.status','');
        $type = input('get.type','');
        if($keyWords != ''){
            $map[]=['think_stock_inventory.id|think_shop_lists.name', 'like', "%".$keyWords."%"];
        }
        if($startTime){
            $map[]=['think_stock_inventory.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_stock_inventory.create_time','<',strtotime($endTime)];
        }
        if($status != ''){
            $map[]=['think_stock_inventory.status','=',$status];
        }
        if($type != ''){
            $map[]=['think_stock_inventory.type','=',$type];
        }


        $res = $StockInventoryModel->getPaginate($map);
        $lists = $res->toArray();
        return json(['code'=>1,'data'=>['count'=>$lists['total'],'lists'=>$lists['data'],'status'=>$StockInventoryModel->orderStatus,'type'=>$StockInventoryModel->orderType],'msg'=>'']);
    }
    //New inventory
    public function crate($type=1,$remarks=''){
       $StockInventoryModel = new StockInventoryModel();
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
       $id = $StockInventoryModel->createInventory($shop_id,$type,$this->userName,$this->userId,$remarks);
       if(!$id){
		return json(['code'=>0,'data'=>[],'msg'=>'create failed']);
       }
       //full inventory
        $data=[];
       if($type == 1){
            // Take out all the items of the user
          $GoodsStock = new GoodsStock();
          $allGods = $GoodsStock->where(['shop_id'=>$shop_id])->select();
          $StockInventoryListsModel = new StockInventoryListsModel();

          foreach ($allGods as $v){
            $data[]=[
                'inventory_id'=>$id,
                'spec_id'=>$v['spec_id'],
                'goods_id'=>$v['goods_id'],
                'system_stock'=>$v['stock'],
                'inventory_stock'=>0,
                'difference'=>0-$v['stock'],
                'remarks'=>'',
                'cost_price'=>$v['cost_price'],
                'diff_money'=>$v['cost_price']*(0-$v['stock']),
                'update_time'=>time(),
                'create_time'=>time()
            ];
          }
          if($data){
              $StockInventoryListsModel->insertAll($data);
          }

       }
       return json(['code'=>1,'data'=>['id'=>$id],'msg'=>'Created successfully']);
    }

    //file import
    public function inport(){
        $data = StockCommon::import();
        $d = $data['data'];
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $GoodsStock =  new GoodsStock();
        $lists=[];
        foreach ($d as $k => $v){
			// Check first if it exists
            $stock = $GoodsStock->getOneStockDetailsBySku($shop_id,$k);
            // Update if it exists
            if($stock){
                unset($stock['market_price']);
                unset($stock['shop_price']);
                unset($stock['trade_price']);
                $stock['system_stock']=$stock['stock'];
                unset($stock['stock']);
                $stock['inventory_stock']=$v;
                $stock['difference']=$stock['inventory_stock']-$stock['system_stock'];
                $stock['diff_money']=sprintf("%.2f",$stock['difference']*$stock['cost_price']);
            }else{
                $SpecGoodsModel =  new SpecGoodsModel();
                $stock =  $SpecGoodsModel->getOneDetailsBySku($k);
                if($stock){
                    unset($stock['market_price']);
                    unset($stock['shop_price']);
                    unset($stock['trade_price']);
                    $stock['system_stock']=0;
                    $stock['inventory_stock']=$v;
                    $stock['difference']=$stock['inventory_stock']-$stock['system_stock'];
                    $stock['diff_money']=$stock['difference']*$stock['cost_price'];
                }else{
                   return json(['code'=>0,'data'=>[],'msg'=>'model '.$k.' does not exist']);
                }
            }
            $lists[]=$stock;
        }
        return json(['code'=>1,'data'=>$lists,'msg'=>'']);



    }
    //Inventory list
    public function lists(){
        if($this->request->isPost()){
            $order_id= input('post.inventory_id');
            $post= input('post.');
            $post_goods= input('post.data');
            $goods=[];
            $shop_id = $this->shopId;
            if(!$shop_id){
                $shop_id=config('config.shop_default_manage');
            }
            $StockInventoryModel = new StockInventoryModel();
            $map=['id'=>$order_id];
            $map['shop_id']=$shop_id;
            $order_info = $StockInventoryModel->where($map)->find();
            if(!$order_info ||$order_info->getData('status') != 1){
				return json(['code'=>0,'data'=>[],'msg'=>'current state cannot be operated']);
            }
            if($post_goods){
                $SpecGoodsModel= new SpecGoodsModel();
                $GoodsStock = new GoodsStock();
                foreach ($post_goods['spec_id'] as $k => $v){
                    $data=[];
                    // Find this based on the submitted goods_spec_id
                    $SpecGoods_info = $SpecGoodsModel->where(['spec_id'=>$v])->find();
                    //find current stock
                    $stock_info = $GoodsStock->where(['shop_id'=>$shop_id,'spec_id'=>$v])->find();
                    if($stock_info){
                        $stock =$stock_info['stock'];
                        $cost_price =$stock_info['cost_price'];
                    }else{
                        $stock =0;
					$cost_price =$SpecGoods_info['cost_price'];//TODO::This place should be questionable if the subordinates have no inventory during the inventory, and the purchase price of the mall is taken.
                    }
                    if($post_goods['inventory_stock'][$k] < 0){
                        return json(['code'=>0,'data'=>[],'msg'=>'counting inventory cannot be negative']);
                    }
                    $data['inventory_id']=$order_id;
                    $data['spec_id']=$v;
                    $data['goods_id']=$SpecGoods_info['goods_id'];
                    $data['system_stock']=$stock;
                    $data['inventory_stock']=$post_goods['inventory_stock'][$k];
                    $data['difference']= $data['inventory_stock']-$data['system_stock'];//Inventory inventory minus system inventory
                    $data['remarks']=$post_goods['remarks'][$k];
                    $data['cost_price']=$cost_price;
                    $data['diff_money']=$data['cost_price']*$data['difference'];//The purchase price is multiplied by the difference
                    $data['update_time']=time();
                    $data['create_time']=time();
                    $goods[]=$data;
                }
            }
            Db::startTrans();
            try {
                $order_info->save($post);
                $StockInventoryListsModel = new StockInventoryListsModel();
			// first delete and save
                $StockInventoryListsModel->where(['inventory_id'=>$order_id])->delete();
                $StockInventoryListsModel->saveAll($goods);
                Db::commit();
            } catch (Exception $e) {
                // validation failed output error message
                Db::rollback();
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            //add log
           // PurchaseOrderLogModel::operate($this->userId,$order_id,$this->userName." Modified purchase order:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
        }

        $order_id= input('get.inventory_id');
        $StockInventoryModel = new StockInventoryModel();
        $map=['id'=>$order_id];
        if($this->shopId){
            $map['shop_id']=$this->shopId;
        }
        $order_info = $StockInventoryModel->where($map)->find();

        if(!$order_info){
            return json(['code'=>0,'data'=>[],'msg'=>'The information was not found']);
        }
        $StockInventoryListsModel = new StockInventoryListsModel();
        $lists = $StockInventoryListsModel->getLists(['inventory_id'=>$order_id]);

        $order_info->shop_name=$order_info->getShopName();

        return json(['code'=>1,'data'=>['info'=>$order_info,'lists'=>$lists],'msg'=>'']);

    }
    //Inventory completed
    public function complete(){
        $order_id= input('inventory_id');
        $StockInventoryModel = new StockInventoryModel();
        $map=['id'=>$order_id];
        if($this->shopId){
            $map['shop_id']=$this->shopId;
        }
        $order_info = $StockInventoryModel->where($map)->find();
        if(!$order_info || $order_info->getData('status') != 1){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be operated']);
        }
        $order_info->status=2;
        Db::startTrans();
        try {
            if($order_info->save()){
                $StockInventoryListsModel = new StockInventoryListsModel();
                $GoodsStock = new GoodsStock();
			//modify the order list
                $all_stock = $StockInventoryListsModel->where(['inventory_id'=>$order_id])->select()->toArray();
                foreach ($all_stock as $v){
                    $GoodsStock->inventoryStock($this->userId,$order_id,$order_info->shop_id,$v);
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
        $order_id= input('inventory_id');
        $StockInventoryModel = new StockInventoryModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['shop_id']=$this->shopId;
        }
        $order_info = $StockInventoryModel->where($map)->find();

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
        $GoodsStock =  new GoodsStock();
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
		// Check first if it exists
        $stock = $GoodsStock->getOneStockDetails($shop_id,$spec_id);
        // Update if it exists
        if($stock){
            unset($stock['market_price']);
            unset($stock['shop_price']);
            unset($stock['trade_price']);
            $stock['system_stock']=$stock['stock'];
            unset($stock['stock']);
            $stock['inventory_stock']=0;
            $stock['difference']=$stock['inventory_stock']-$stock['system_stock'];
            $stock['diff_money']=$stock['difference']*$stock['cost_price'];
            return json(['code'=>1,'data'=>$stock,'msg'=>'']);

        }else{
            $SpecGoodsModel =  new SpecGoodsModel();
            $stock =  $SpecGoodsModel->getOneDetails($spec_id);
            unset($stock['market_price']);
            unset($stock['shop_price']);
            unset($stock['trade_price']);
            $stock['system_stock']=0;
            $stock['inventory_stock']=0;
            $stock['difference']=$stock['inventory_stock']-$stock['system_stock'];
            $stock['diff_money']=sprintf("%.2f",$stock['difference']*$stock['cost_price']);;
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
            unset($stock['market_price']);
            unset($stock['shop_price']);
            unset($stock['trade_price']);
            $stock['system_stock']=$stock['stock'];
            unset($stock['stock']);
            $stock['inventory_stock']=0;
            $stock['difference']=$stock['inventory_stock']-$stock['system_stock'];
            $stock['diff_money']=sprintf("%.2f",$stock['difference']*$stock['cost_price']);;
            return json(['code'=>1,'data'=>$stock,'msg'=>'']);

        }else{
            $SpecGoodsModel =  new SpecGoodsModel();
            $stock =  $SpecGoodsModel->getOneDetailsBySku($spec_sku);
            unset($stock['market_price']);
            unset($stock['shop_price']);
            unset($stock['trade_price']);
            $stock['system_stock']=0;
            $stock['inventory_stock']=0;
            $stock['difference']=$stock['inventory_stock']-$stock['system_stock'];
            $stock['diff_money']=$stock['difference']*$stock['cost_price'];
            return json(['code'=>1,'data'=>$stock,'msg'=>'']);
        }
    }

    public function stock(){
        $GoodsStock= new GoodsStock();
        $map=[];
        $shopId = input('shop_id',0);
        if($this->shopId){
            $map[]=['think_goods_stock.shop_id','=',$this->shopId];
        }else if ($shopId){
            $map[]=['think_goods_stock.shop_id','=',$shopId];
        }
        //goods_name goods_sn goods_id spec_id  spec_name spec_sku

        $keyWords = input('get.keyWords','','trim');
        if($keyWords != ''){
            $map[]=['goods_name|goods_sn|think_goods_stock.goods_id|think_goods_stock.spec_id|spec_name|spec_sku', 'like', "%".$keyWords."%"];
        }
        $res = $GoodsStock->getPaginate($map);
        $lists = $res->toArray();
        $ShopListsModel = new ShopListsModel();
        $shop_map=[];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
        }
        $sum = $GoodsStock->getSumArr($map);
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data']],'shop_lists'=>$ShopListsModel->getKeyVal($shop_map),'msg'=>'']);
    }
    public function stockLog($spec_id){
       $GoodsStockLog =  new GoodsStockLog();
       $map[]=['think_goods_stock_log.spec_id','=',$spec_id];
       if($this->shopId){
           $map[]=['think_goods_stock_log.shop_id','=',$this->shopId];
       }
       $limits = input('get.limit',15);
       $page = input('get.page',1);
       $count =  $GoodsStockLog->where($map)->count();
       $lists =  $GoodsStockLog->field('think_goods_stock_log.*,think_admin.username')->join('think_admin','think_goods_stock_log.uid=think_admin.id','left')->where($map)->page($page,$limits)->order('id desc')->select();
       return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$lists],'msg'=>'']);
    }
    public function PriceLog($spec_id){
        $GoodsPriceLog = new GoodsPriceLog();
        $map[]=['spec_id','=',$spec_id];
        if($this->shopId){
            $map[]=['think_goods_price_log.shop_id','=',$this->shopId];
        }
        $limits = input('get.limit',15);
        $page = input('get.page',1);
        $count =  $GoodsPriceLog->where($map)->count();
        $lists =  $GoodsPriceLog->field('think_goods_price_log.*,think_admin.username')->join('think_admin','think_goods_price_log.uid=think_admin.id')->where($map)->page($page,$limits)->order('id desc')->select();
        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$lists],'msg'=>'']);
    }

    public function periodic(){
        $map=[];
        $startTime = input('get.startTime','','trim');
        $endTime = input('get.endTime','','trim');
        $shop_id = input('get.shop_id',0,'trim');

        if($this->shopId){
            $map[]=['shop_id','=',$this->shopId];
            $shop_id=$this->shopId;
        }else  if($shop_id){
            $map[]=['shop_id','=',$shop_id];
            $shop_id=$shop_id;
        }
        if($startTime){
            $startTime = strtotime($startTime);
            $map[]=['think_goods_stock_log.create_time','>',$startTime];
        }else{
            $startTime =time()-3600*24*31;
            $map[]=['think_goods_stock_log.create_time','>',time()-3600*24*31];
        }
        if($endTime){
            $endTime=strtotime($endTime);
            $map[]=['think_goods_stock_log.create_time','<',$endTime];
        }else{
            $endTime=time();
            $map[]=['think_goods_stock_log.create_time','<',time()];
        }


        $types = Db::name('goods_stock_log')->field('type,remarks')->order('type asc')->group('type,remarks')->select()->toArray();
        $res = Db::name('goods_stock_log')->field('spec_id,type,remarks,sum(num) as count,sum(num*price) as price')->order('type asc')->where($map)->order('create_time asc')->group('spec_id,type')->select()->toArray();
      //find one
        $data = [];
        foreach ($res as $v){
            $data[$v['spec_id']][$v['type']] = $v;
        }


        foreach ($data as $a => $b){
            //Get prices and SKUs
           $spec_sku = Db::name('spec_goods')->where(['spec_id'=>$a])->value('spec_sku');
            foreach ($types as $c){
                if(!key_exists($c['type'],$b)){
                    $c['count']="0";
                    $c['price']="0.00";
                    $c['spec_sku']=$spec_sku;
                    $data[$a][$c['type']]=$c;
                }else{
                    $data[$a][$c['type']]['spec_sku']=$spec_sku;
                }
            }


            $map3 =[['think_goods_stock_log.create_time','>',$startTime],['spec_id','=',$a]];
            $map4 =[['think_goods_stock_log.create_time','<',$endTime],['spec_id','=',$a]];

            $field3 ='spec_id,remarks,system_stock as count,(system_stock*price)as price';
            $field4 ='spec_id,remarks,(system_stock+num) as count,((system_stock+num)*price)as price';
            if($shop_id){
                $field3 ='spec_id,remarks,shop_stock as count,(shop_stock*price)as price';
                $field4 ='spec_id,remarks,(shop_stock+num) as count,((shop_stock+num)*price)as price';
                $map3[]= ['shop_id','=',$shop_id];
                $map4[]= ['shop_id','=',$shop_id];
            }
            //get the first
            $arr = Db::name('goods_stock_log')->field($field3)->where($map3)->order('create_time asc')->find();

            $arr['remarks']='Early stage';
            $arr['type']=0;
            $arr['count']=$arr['count'];
            $arr['price']=$arr['price'];
            $arr['spec_sku']=$spec_sku;
            array_unshift($data[$a],$arr);
            //get the last one
            $arr2 = Db::name('goods_stock_log')->field($field4)->where($map4)->order('create_time desc')->find();
            $arr2['remarks']='end of period';
            $arr2['type']=100;
            $arr2['count']=$arr2['count'];
            $arr2['price']=$arr2['price'];
            $arr2['spec_sku']=$spec_sku;
            array_push($data[$a],$arr2);
            //Sort processing
            $data[$a]= $this->array_sequence($data[$a],'type');

        }

        $shop_map=[];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
        }
        $ShopListsModel =   new ShopListsModel();
        return json(['code'=>1,'data'=>['lists'=>$data,'shop_lists'=>$ShopListsModel->getKeyVal($shop_map)],'msg'=>'']);



    }
    /**
	 * 2D array sorted by field
     * params array $array the array to be sorted
     * params string $field field to sort by
     * params string $sort sort order flag SORT_DESC descending; SORT_ASC ascending
     */
    protected  function array_sequence($data = [], $field, $sort = 'SORT_ASC')
    {

        $arrSort = [];
        foreach ($data as $id => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$id] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $data);
        return $data;
    }

    public function yujing(){
       $res = Db::table('(SELECT goods_id,spec_id,sum(stock) as stock FROM `think_goods_stock` group by spec_id) as goods_stock')->field('goods_stock.goods_id,goods_stock.spec_id,goods_stock.stock,think_goods.goods_name,think_goods.goods_sn,think_spec_goods.spec_name,think_goods.original_img')->join('think_goods','think_goods.id = goods_stock.goods_id','left')->join('think_spec_goods','think_spec_goods.spec_id=goods_stock.spec_id','left')->where('goods_stock.stock','<',1000)->select();

       return json(['code'=>1,'data'=>$res,'msg'=>'']);
    }




}