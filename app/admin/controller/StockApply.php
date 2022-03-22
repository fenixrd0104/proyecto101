<?php

namespace app\admin\controller;
use app\admin\model\GoodsSupplierModel;
use app\admin\model\PurchaseOrderListsModel;
use app\admin\model\PurchaseOrderModel;
use app\admin\model\PurchaseOrderLogModel;
use app\admin\model\ShopListsModel;
use app\admin\model\SpecGoodsModel;
use app\admin\model\StockApplyListsModel;
use app\admin\model\StockApplyLogModel;
use app\admin\model\StockApplyModel;
use app\admin\model\StockPriceListsModel;
use app\common\service\StockCommon;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Db;

class StockApply extends Base
{
    public function getShopLists(){
        $shop_id =$this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $ShopListsModel =  new ShopListsModel();
        $keyWords = input('get.keyWords','');
        //All child stores of own parent store + parent store
        $shop_id_arr = StockCommon::getStockTransferShopId($shop_id);
        if(!$shop_id_arr['code']){
            return json($shop_id_arr);
        }
        $map[] = ['status','=',1];
        $map[] = ['id','in',$shop_id_arr['data']];
        if($keyWords != ''){
            $map[] = ['id|name|phone','like','%'.$keyWords.'%'];
        }
        $shop_lists = $ShopListsModel->field('id,name,phone,address')->where($map)->select();
        return json(['code'=>1,'data'=> $shop_lists,'msg'=>'']);
    }
    //Order List
    public function toIndex(){
        $StockApplyModel = new StockApplyModel();
        $map=[];
        if($this->shopId){
            $map[]=['to_shop','=',$this->shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');

        $orderStatus = input('get.orderStatus','');
        $deliveryStatus = input('get.deliveryStatus','');
        if($keyWords != ''){
            $map[]=['a.name|b.name|to_account|from_account', 'like', "%".$keyWords."%"];
        }

        if($startTime){
            $map[]=['think_stock_apply.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_stock_apply.create_time','<',strtotime($endTime)];
        }
        if($orderStatus != ''){
            $map[]=['order_status','=',$orderStatus];
        }
        if($deliveryStatus != ''){
            $map[]=['delivery_status','=',$deliveryStatus];
        }

        $res = $StockApplyModel->getPaginate($map);
        $sum = $StockApplyModel->getSumArr($map);
        $lists = $res->toArray();
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data'],'orderStatus'=>$StockApplyModel->orderStatus,'deliveryStatus'=>$StockApplyModel->deliveryStatus],'msg'=>'']);
    }
		//Create Order
    public function toCreate($form_shop){
        // store list
        $shop_id =$this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $shop_id_arr = StockCommon::getStockTransferShopId($shop_id);
        if(!$shop_id_arr['code']){
            return json($shop_id_arr);
        }
        if(!in_array($form_shop,$shop_id_arr['data'])){
		return json(['code'=>0,'data'=>[],'msg'=>'Cannot apply for this store to transfer goods']);
        }
        //Get store information
        $ShopListsModel = new ShopListsModel();
        $shop =$ShopListsModel->getOneSubshop($shop_id);
        if(!$shop){
            return json(['code'=>0,'data'=>[],'msg'=>'The store does not exist']);
        }
        //Create Order
        $StockApplyModel = new StockApplyModel();
        $order_id = $StockApplyModel->createOrder($form_shop,$shop_id,$this->userId,$this->userName,'');

        //add log
        StockApplyLogModel::operate($this->userId,$order_id,$this->userName." Create transfer application:".$order_id);
        $StockApplyModel->order_money='0.00';
        $StockApplyModel->to_shop_name=$shop->name;
        $StockApplyModel->from_shop_name=Db::name('shop_lists')->where(['id'=>$form_shop])->value('name');

        return json(['code'=>1,'data'=>['info'=>$StockApplyModel,'lists'=>[]],'msg'=>'']);

    }

   //save dingd
    public function toLists(){
        if($this->request->isPost()){
            $order_id= input('post.order_id');
            $post_goods= input('post.data');
            $post= input('post.');
            $goods=[];
            $order_money=0;
            $order_num=0;
            if($post_goods){
                $SpecGoodsModel= new SpecGoodsModel();
                foreach ($post_goods['spec_id'] as $k => $v){
                    $data=[];
                    // Find this based on the submitted goods_spec_id
                    $SpecGoodsGoodsId =$SpecGoodsModel->where(['spec_id'=>$v])->value('goods_id');
                    $data['order_id']=$order_id;
                    $data['spec_id']=$v;
                    $data['goods_id']=$SpecGoodsGoodsId;
                    $data['price']=$post_goods['price'][$k];
                    $data['num']=$post_goods['num'][$k];
                    $data['remarks']=$post_goods['remarks'][$k];;
                    $data['total_money']=$post_goods['price'][$k]*$post_goods['num'][$k];
                    $data['delivery_quantity']=0;
                    $order_money+= $data['total_money'];
                    $order_num+= $data['num'];
                    $goods[]=$data;
                }
            }
            try {
                $StockApplyModel = new StockApplyModel();
                $map=['id'=>$order_id];
                $shop_id = $this->shopId;
                if(!$shop_id){
                    $shop_id=config('config.shop_default_manage');
                }
                $map['to_shop']=$shop_id;
                $order_info = $StockApplyModel->where($map)->find();
              
                if(!$order_info ||$order_info->getData('order_status') != 1){
                    return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be operated']);
                }
                //Consignee Phone Address Remarks
                $post['order_money']=$order_money;
                $post['order_num']=$order_num;
                $order_info->save($post);
                //Commodity list Commodity ID Price Quantity Remarks
                $StockApplyListsModel = new StockApplyListsModel();
			// first delete and save
                $StockApplyListsModel->where(['order_id'=>$order_id])->delete();
                $StockApplyListsModel->saveAll($goods);
            } catch (Exception $e) {
                // validation failed output error message
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            //add log
            PurchaseOrderLogModel::operate($this->userId,$order_id,$this->userName." Modified Purchase Order:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
        }
        $order_id= input('get.order_id');
        $StockApplyModel = new StockApplyModel();
        $map=['id'=>$order_id];
        if($this->shopId){
            $map['to_shop']=$this->shopId;
        }
        $order_info = $StockApplyModel->where($map)->find();
        if(!$order_info){
		return json(['code'=>0,'data'=>[],'msg'=>'check no order']);
        }
        $StockApplyListsModel = new StockApplyListsModel();
        $lists = $StockApplyListsModel->getLists(['order_id'=>$order_id]);

        //Get store information
        $ShopListsModel = new ShopListsModel();
        $shop =$ShopListsModel->getOneSubshop($order_info->to_shop);
        if(!$shop){
            return json(['code'=>0,'data'=>[],'msg'=>'The store does not exist']);
        }
        $order_info->to_shop_name=$shop->name;
        $order_info->from_shop_name=Db::name('shop_lists')->where(['id'=>$order_info->from_shop])->value('name');
        return json(['code'=>1,'data'=>['info'=>$order_info,'lists'=>$lists],'msg'=>'']);

    }

		//Sure
    public function toComplete(){
        $order_id= input('order_id');
        $StockApplyModel = new StockApplyModel();
        $map=['id'=>$order_id];
        if($this->shopId){
            $map['to_shop']=$this->shopId;
        }
        $order_info = $StockApplyModel->where($map)->find();
        if(!$order_info || $order_info->getData('order_status') != 1){
            return json(['code'=>0,'data'=>[],'msg'=>'current state cannot be operated']);
        }

        $order_info->order_status=2;
        if($order_info->save()){
            $StockApplyListsModel = new StockApplyListsModel();
            //modify the order list
            $StockApplyListsModel->where(['order_id'=>$order_id])->update(['order_status'=>2]);
            //add log
            StockApplyLogModel::operate($this->userId,$order_id,$this->userName." Submitted a transfer application:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }

    //cancel order
    public function toCancel(){
        $order_id= input('order_id');
        $StockApplyModel = new StockApplyModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['to_shop']=$this->shopId;
        }
        $order_info = $StockApplyModel->where($map)->find();

        if(!$order_info || $order_info->getData('order_status') != 1 ){
			return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be cancelled']);
        }
        $order_info->order_status=3;
       if($order_info->save()){
           //modify the order list
           $StockApplyListsModel = new StockApplyListsModel();
           $StockApplyListsModel->where(['order_id'=>$order_id])->update(['order_status'=>3]);
           //add log
           StockApplyLogModel::operate($this->userId,$order_id,$this->userName." Canceled the transfer application:".$order_id);
           return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }


    //transfer list
    public function fromIndex(){
            $StockApplyModel = new StockApplyModel();
            $map=[];
            if($this->shopId){
                $map[]=['from_shop','=',$this->shopId];
            }
            $StockApplyModel->orderStatus[2]='pending review';

            $startTime = input('get.startTime',0,'trim');
            $endTime = input('get.endTime',0,'trim');
            $keyWords = input('get.keyWords','','trim');

            $orderStatus = input('get.orderStatus','');
            $deliveryStatus = input('get.deliveryStatus','');
            if($keyWords != ''){
                $map[]=['a.name|b.name|to_account|from_account', 'like', "%".$keyWords."%"];
            }

            if($startTime){
                $map[]=['think_stock_apply.create_time','>',strtotime($startTime)];
            }
            if($endTime){
                $map[]=['think_stock_apply.create_time','<',strtotime($endTime)];
            }
            if($orderStatus != '' && ($orderStatus==2 || $orderStatus==4 || $orderStatus == 5)){
                $map[]=['order_status','=',$orderStatus];
            }else{
                $map[]=['order_status','in',[2,4,5]];
            }
            if($deliveryStatus != ''){
                $map[]=['delivery_status','=',$deliveryStatus];
            }

            $res = $StockApplyModel->getPaginate($map);
            $lists = $res->toArray();
            $order_Status=$StockApplyModel->orderStatus;
            $sum = $StockApplyModel->getSumArr($map);
            $order_Status[2]='pending review';
            unset($order_Status[1]);
            unset($order_Status[3]);
            return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data'],'orderStatus'=>$order_Status,'deliveryStatus'=>$StockApplyModel->deliveryStatus],'msg'=>'']);
        }

        //agree to apply
    public function fromAgree(){
        $order_id= input('order_id');
        $from_remarks= input('from_remarks','');
        $StockApplyModel = new StockApplyModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['from_shop']=$this->shopId;
        }
        $order_info = $StockApplyModel->where($map)->find();

        if(!$order_info || $order_info->getData('order_status') != 2 ){
            return json(['code'=>0,'data'=>[],'msg'=>'Current status cannot agree']);
        }
        $order_info->order_status=4;
        $order_info->from_remarks=$from_remarks;
        $order_info->from_uid=$this->userId;
        $order_info->from_account=$this->userName;
        if($order_info->save()){
			//modify the order list
            $StockApplyListsModel = new StockApplyListsModel();
            $StockApplyListsModel->where(['order_id'=>$order_id])->update(['order_status'=>4]);
            //add log
            StockApplyLogModel::operate($this->userId,$order_id,$this->userName." Passed the transfer application:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }
    //reject application
    public function fromRefuse(){
        $order_id= input('order_id');
        $StockApplyModel = new StockApplyModel();
        $from_remarks= input('from_remarks','');
        $map=['id'=>$order_id];
        if($this->shopId){
            $map['from_shop']=$this->shopId;
        }
        $order_info = $StockApplyModel->where($map)->find();

        if(!$order_info || $order_info->getData('order_status') != 2 ){
            return json(['code'=>0,'data'=>[],'msg'=>'Current status cannot be rejected']);
        }
        $order_info->order_status=5;
        $order_info->from_remarks=$from_remarks;
        $order_info->from_uid=$this->userId;
        $order_info->from_account=$this->userName;
        if($order_info->save()){
		//modify the order list
            $StockApplyListsModel = new StockApplyListsModel();
            $StockApplyListsModel->where(['order_id'=>$order_id])->update(['order_status'=>5]);
            //add log
            StockApplyLogModel::operate($this->userId,$order_id,$this->userName." Passed the transfer application:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }

    //Apply for transfer list
    public function fromLists(){
        $order_id= input('get.order_id');
        $StockApplyModel = new StockApplyModel();
        $map[]=['id','=',$order_id];
        if($this->shopId){
            $map[]=['from_shop','=',$this->shopId];
        }
        $map[]=['order_status','in',[2,4,5]];
        $order_info = $StockApplyModel->where($map)->find();
        if(!$order_info){
		return json(['code'=>0,'data'=>[],'msg'=>'check no order']);
        }
        $StockApplyListsModel = new StockApplyListsModel();
        $lists = $StockApplyListsModel->getLists(['order_id'=>$order_id]);

        //Get store information
        $ShopListsModel = new ShopListsModel();
        $shop =$ShopListsModel->getOneSubshop($order_info->to_shop);
        if(!$shop){
            return json(['code'=>0,'data'=>[],'msg'=>'The store does not exist']);
        }
        $order_info->to_shop_name=$shop->name;
        $order_info->from_shop_name=Db::name('shop_lists')->where(['id'=>$order_info->from_shop])->value('name');
        return json(['code'=>1,'data'=>['info'=>$order_info,'lists'=>$lists],'msg'=>'']);

    }
    //log
    public function log($order_id){
        return json(['code'=>1,'data'=>StockApplyLogModel::log($order_id),'msg'=>'']);
    }

}