<?php

namespace app\merchant\controller;
use app\merchant\model\GoodsSupplierModel;
use app\merchant\model\PurchaseOrderListsModel;
use app\merchant\model\PurchaseOrderModel;
use app\merchant\model\PurchaseOrderLogModel;
use app\merchant\model\ShopListsModel;
use app\merchant\model\SpecGoodsModel;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Db;

class PurchaseOrder extends Base
{
    //Order List
    public function listsOrder(){
        $PurchaseOrderModel = new PurchaseOrderModel();
        $map=[];
        if($this->shopId){
            $map[]=['receiving_shop','=',$this->shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');

        $orderStatus = input('get.orderStatus','');
        $receiptStatus = input('get.receiptStatus','');
        if($keyWords != ''){
            $map[]=['supplier_name|consignee|phone', 'like', "%".$keyWords."%"];
        }

        if($startTime){
            $map[]=['think_purchase_order.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_purchase_order.create_time','<',strtotime($endTime)];
        }
        if($orderStatus != ''){
            $map[]=['order_status','=',$orderStatus];
        }
        if($receiptStatus != ''){
            $map[]=['receipt_status','=',$receiptStatus];
        }

        $res = $PurchaseOrderModel->getPaginate($map);
        $sum = $PurchaseOrderModel->getSumArr($map);
        $lists = $res->toArray();
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data'],'orderStatus'=>$PurchaseOrderModel->orderStatus,'receiptStatus'=>$PurchaseOrderModel->receiptStatus],'msg'=>'']);
    }
    //Create Order
    public function crateOrder($supplier_id){

        //get supplier information
        $GoodsSupplierModel = new GoodsSupplierModel();
        $supplier = $GoodsSupplierModel->getOne($supplier_id);
        if(!$supplier){
            return json(['code'=>0,'data'=>[],'msg'=>'supplier does not exist']);
        }
        //Get store information
        $ShopListsModel = new ShopListsModel();
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $shop =$ShopListsModel->getOneSubshop($shop_id);
        if(!$shop){
            return json(['code'=>0,'data'=>[],'msg'=>'The store does not exist']);
        }

        //Address information
        $addr = new \app\common\model\Address();
        $address = $addr->getAddr($shop->province,$shop->city,$shop->area);
        //Create Order
        $PurchaseOrderModel = new PurchaseOrderModel();

        $order_id = $PurchaseOrderModel->createOrder($shop_id,$supplier_id, $shop->forUser->real_name,$shop->phone,$address.' '.$shop->address);

        //add log
        PurchaseOrderLogModel::operate($this->userId,$order_id,$this->userName." Create Purchase Order:".$order_id);

        $PurchaseOrderModel->supplier_name=$supplier->supplier_name;
        $PurchaseOrderModel->shop_name=$shop->name;

        return json(['code'=>1,'data'=>['info'=>$PurchaseOrderModel,'lists'=>[]],'msg'=>'']);

    }

    //save dingd
    public function saveOrder(){
        if($this->request->isPost()){
            $order_id= input('post.order_id');
            $post_goods= input('post.goods');
            $post= input('post.');
            $goods=[];
            $order_money=0;

            if($post_goods){
                $SpecGoodsModel= new SpecGoodsModel();
                foreach ($post_goods['spec_id'] as $k => $v){
                    $data=[];
                    //Find this based on the submitted goods_spec_id
                    $SpecGoodsGoodsId =$SpecGoodsModel->where(['spec_id'=>$v])->value('goods_id');
                    $data['order_id']=$order_id;
                    $data['spec_id']=$v;
                    $data['goods_id']=$SpecGoodsGoodsId;
                    $data['purchase_price']=$post_goods['price'][$k];
                    $data['purchase_num']=$post_goods['num'][$k];
                    $data['remarks']=$post_goods['remarks'][$k];;
                    $data['total_money']=$post_goods['price'][$k]*$post_goods['num'][$k];
                    $data['receipt_quantity']=0;
                    $order_money+= $data['total_money'];
                    $goods[]=$data;
                }
            }
            try {
                $PurchaseOrderModel = new PurchaseOrderModel();
                $map=['id'=>$order_id];
                $shop_id = $this->shopId;
                if(!$shop_id){
                    $shop_id=config('config.shop_default_manage');
                }
                $map['receiving_shop']=$shop_id;
                $order_info = $PurchaseOrderModel->where($map)->find();
              
                if(!$order_info ||$order_info->getData('order_status') != 0){
                   return json(['code'=>0,'data'=>[],'msg'=>'current state cannot be operated']);
                }
                //Consignee Phone Address Remarks
                $post['order_money']=$order_money;
                $order_info->save($post);
                //Commodity list Commodity ID Price Quantity Remarks
                $PurchaseOrderListsModel = new PurchaseOrderListsModel();
                // first delete and save
                $PurchaseOrderListsModel->where(['order_id'=>$order_id])->delete();
                $PurchaseOrderListsModel->saveAll($goods);
            } catch (Exception $e) {
                // validation failed output error message
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            //add log
            PurchaseOrderLogModel::operate($this->userId,$order_id,$this->userName." Modified Purchase Order:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
        }
        $order_id= input('get.order_id');

        $PurchaseOrderModel = new PurchaseOrderModel();
        $map=['id'=>$order_id];
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $order_info = $PurchaseOrderModel->where($map)->find();

        $PurchaseOrderListsModel = new PurchaseOrderListsModel();
        $lists = $PurchaseOrderListsModel->getLists(['order_id'=>$order_id]);
       //get supplier information
        $GoodsSupplierModel = new GoodsSupplierModel();
        $supplier = $GoodsSupplierModel->getOne($order_info->supplier_id);
        if(!$supplier){
            return json(['code'=>0,'data'=>[],'msg'=>'supplier does not exist']);
        }


        //Get store information
        $ShopListsModel = new ShopListsModel();
        $shop =$ShopListsModel->getOneSubshop($order_info->receiving_shop);
        if(!$shop){
            return json(['code'=>0,'data'=>[],'msg'=>'The store does not exist']);
        }

        $order_info->supplier_name=$supplier->supplier_name;
        $order_info->shop_name=$shop->name;

        return json(['code'=>1,'data'=>['info'=>$order_info,'lists'=>$lists],'msg'=>'']);



    }

    //Sure
    public function completeOrder(){
        $order_id= input('order_id');
        $PurchaseOrderModel = new PurchaseOrderModel();
        $map=['id'=>$order_id];
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $order_info = $PurchaseOrderModel->where($map)->find();
        if(!$order_info || $order_info->getData('order_status') != 0){
            return json(['code'=>0,'data'=>[],'msg'=>'current state cannot be operated']);
        }

        $order_info->order_status=1;
        if($order_info->save()){
            $PurchaseOrderListsModel = new PurchaseOrderListsModel();
            //modify the order list
            $PurchaseOrderListsModel->where(['order_id'=>$order_id])->update(['order_status'=>1]);
            //add log
            PurchaseOrderLogModel::operate($this->userId,$order_id,$this->userName." Submitted purchase order:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }

    //cancel order
    public function cancelOrder(){
        $order_id= input('order_id');
        $PurchaseOrderModel = new PurchaseOrderModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $order_info = $PurchaseOrderModel->where($map)->find();

        if(!$order_info || $order_info->getData('receipt_status') != 0 ){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be cancelled']);
        }
        $order_info->order_status=2;
       if($order_info->save()){
           //modify the order list
           $PurchaseOrderListsModel = new PurchaseOrderListsModel();
           $PurchaseOrderListsModel->where(['order_id'=>$order_id])->update(['order_status'=>2]);
           //add log
           PurchaseOrderLogModel::operate($this->userId,$order_id,$this->userName." canceled purchase order:".$order_id);
           return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }

    //log
    public function log($order_id){
        return json(['code'=>1,'data'=>PurchaseOrderLogModel::log($order_id),'msg'=>'']);;
    }


}