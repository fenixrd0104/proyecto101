<?php

namespace app\admin\controller;
use app\admin\model\Goods as GoodsModel;
use app\admin\model\GoodsStock;
use app\admin\model\GoodsSupplierModel;

use app\admin\model\PurchaseReceiptListsModel;
use app\admin\model\PurchaseReceiptModel;
use app\admin\model\PurchaseRefundsListsModel;
use app\admin\model\PurchaseRefundsLogModel;
use app\admin\model\PurchaseRefundsModel;
use app\admin\model\ShopListsModel;
use app\admin\model\SpecGoodsModel;
use app\admin\model\SupplierMoneyLog;
use app\common\service\StockCommon;
use think\Exception;
use think\facade\Db;

class PurchaseRefunds extends Base
{
    //Orders that have not been received
    public function purchaseOrder(){
      $PurchaseOrder =  new PurchaseReceiptModel();
      $map = [];
      $map[]=['receipt_status','=',1];
        if($this->shopId){
            $map[]=['receiving_shop','=',$this->shopId];
        }
      $Nowpage = input('get.page',1);
      $limits = input('get.limit',15);
        $keywords = input('get.keywords','');
        if($keywords){
            $map[]=['id|supplier_id|consignee|phone','like','%'.$keywords.'%'];
        }
      $count = $PurchaseOrder->where($map)->where('returns_status','<>',2)->count();
      $list = $PurchaseOrder->where($map)->where('returns_status','<>',2)->order('id desc')->page($Nowpage,$limits)->select();
        foreach ($list as $v){
            $v->supplier_name=$v->getSupplier();
        }

      return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list],'msg'=>'']);
    }


		//Create order based on purchase order
    public function crateRefundsByOrder($order_id){

        //read order information
        $PurchaseReceiptModel = new PurchaseReceiptModel();
        $map['id'] = $order_id;
        $map['receipt_status']=1;
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $PurchaseOrderInfo =$PurchaseReceiptModel->where($map)->find();
        if(!$PurchaseOrderInfo){
            return json(['code'=>0,'data'=>[],'msg'=>'The receipt order does not exist']);
        }
        if($PurchaseOrderInfo->getData('receipt_status') != 1 || $PurchaseOrderInfo->getData('returns_status') == 2){
            return json(['code'=>0,'data'=>[],'msg'=>'The receipt order cannot create a receipt']);
        }
        //Get store information
        $ShopListsModel = new ShopListsModel();
        $shop_id =$this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $shop =$ShopListsModel->getOneSubshop($shop_id);
        if(!$shop){
			return json(['code'=>0,'data'=>[],'msg'=>'The store does not exist']);
        }

        //get supplier information
        $GoodsSupplierModel = new GoodsSupplierModel();
        $supplier = $GoodsSupplierModel->getOne($PurchaseOrderInfo->supplier_id);
        if(!$supplier){
            return json(['code'=>0,'data'=>[],'msg'=>'supplier does not exist']);
        }

        //Create Order
        $PurchaseRefundsModel = new PurchaseRefundsModel();
        $r_id = $PurchaseRefundsModel->createOrder($order_id,$PurchaseOrderInfo['supplier_id'],$PurchaseOrderInfo['order_money'],$PurchaseOrderInfo['receiving_shop']);

        //read cargo table
        $PurchaseReceiptListsModel = new PurchaseReceiptListsModel();
        //List the unfinished goods
        $data = $PurchaseReceiptListsModel->where(['receipt_id'=>$order_id])->where("receipt_num",">",Db::raw('returns_num'))->select();
        $PurchaseRefundsListsModel = new PurchaseRefundsListsModel();
        if($data){
            $new_data =  [];
            foreach ($data as $v){
                $new_data[]=[
                    'refunds_id'=>$r_id,
                    'spec_id'=>$v['spec_id'],
                    'goods_id'=>$v['goods_id'],
                    'supplier_id'=>$v['supplier_id'],
                    'refunds_price'=>$v['receipt_price'],
                    'refunds_num'=>$v['receipt_num'],
                    'remarks'=>'',
                    'returns_status'=>0,
                    'total_money'=>$v['total_money']
                ];
            }
		//save
            $PurchaseRefundsListsModel->saveAll($new_data);
        }

        $PurchaseRefundsModel->supplier_name=$supplier->supplier_name;
        $PurchaseRefundsModel->shop_name=$shop->name;

        $lists = $PurchaseRefundsListsModel->getLists(['refunds_id'=>$r_id]);
        //add log
        PurchaseRefundsLogModel::operate($this->userId,$r_id,$this->userName." Create Return Order:".$r_id);
        return json(['code'=>1,'data'=>['info'=>$PurchaseRefundsModel,'lists'=>$lists],'msg'=>'']);

    }

    //Create an order based on the supplier
    public function crateReceiptByNew($supplier_id){
        //get supplier information
        $GoodsSupplierModel = new GoodsSupplierModel();
        $supplier = $GoodsSupplierModel->getOne($supplier_id);
        if(!$supplier){
            return json(['code'=>0,'data'=>[],'msg'=>'supplier does not exist']);
        }

        //Get store information
        $ShopListsModel = new ShopListsModel();
        $shop_id =$this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $shop =$ShopListsModel->getOneSubshop($shop_id);
        if(!$shop){
            return json(['code'=>0,'data'=>[],'msg'=>'The store does not exist']);
        }

		//Create Order
        $PurchaseRefundsModel = new PurchaseRefundsModel();
        $r_id = $PurchaseRefundsModel->createOrder(0,$supplier_id,0,$shop_id);

        $PurchaseRefundsModel->supplier_name=$supplier->supplier_name;
        $PurchaseRefundsModel->shop_name=$shop->name;
        //add log
        PurchaseRefundsLogModel::operate($this->userId,$r_id,$this->userName." Create a receipt order:".$r_id);
        return json(['code'=>1,'data'=>['info'=>$PurchaseRefundsModel,'lists'=>[]],'msg'=>'']);

    }

    //file import
    public function inport(){
        $data = StockCommon::import();
        $supplier_id = input('supplier_id',0);
        $d = $data['data'];
        $SpecGoodsModel = new SpecGoodsModel();
        $lists=[];
        $GoodsModel = new GoodsModel();
        foreach ($d as $k => $v){
            $map=[];
            $info =  $SpecGoodsModel->field('spec_id,spec_key,spec_name,spec_sku,goods_id,cost_price')->where('spec_sku',$k)->find();
            if($info){
                if($supplier_id){
                    $map[] = ['supplier_id','=',$supplier_id];
                }
                $map[] = ['id','=',$info->goods_id];
                $res  =  $GoodsModel->field('id,goods_sn,goods_name,spec_type,cost_price')->where($map)->find();
                if($res){
                    $info->num = $v;
                    $res->spec=$info;
                    $lists[]=$res;
                }else{
				return json(['code'=>0,'data'=>[],'msg'=>'model '.$k.' does not exist 2']);
                }
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'model '.$k.' does not exist 1']);
            }

        }
        return json(['code'=>1,'data'=>$lists,'msg'=>'']);



    }



    //Order List
    public function listsOrder(){
        $PurchaseRefundsModel = new PurchaseRefundsModel();
        $map=[];
        if($this->shopId){
            $map[]=['receiving_shop','=',$this->shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        $orderStatus = input('get.returnsStatus','');
        $receiptStatus = input('get.refundStatus','');
        if($keyWords != ''){
            $map[]=['think_purchase_refunds.id|supplier_name', 'like', "%".$keyWords."%"];
        }

        if($startTime){
            $map[]=['think_purchase_refunds.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_purchase_refunds.create_time','<',strtotime($endTime)];
        }
        if($orderStatus != ''){
            $map[]=['returns_status','=',$orderStatus];
        }
        if($receiptStatus != ''){
            $map[]=['refund_status','=',$receiptStatus];
        }
        $res = $PurchaseRefundsModel->getPaginate($map);
        $sum = $PurchaseRefundsModel->getSumArr($map);
        $lists = $res->toArray();
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data'],'returnsStatus'=>$PurchaseRefundsModel->returnsStatus,'refunStatus'=>$PurchaseRefundsModel->refundStatus],'msg'=>'']);

    }


    //save dingd
    public function saveOrder(){

        if($this->request->isPost()){
            $order_id= input('post.refunds_id');
            $post_goods= input('post.goods');
            $post= input('post.');
            $goods=[];
            $order_money=0;
            try {
                $PurchaseRefundsModel = new PurchaseRefundsModel();
                $map=['id'=>$order_id];
                if($this->shopId){
                    $map['receiving_shop']=$this->shopId;
                }
                $order_info = $PurchaseRefundsModel->where($map)->find();

                if(!$order_info || $order_info->getData('returns_status') != 0){
                    return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be operated']);
                }


                if($post_goods){
                    $SpecGoodsModel= new SpecGoodsModel();

                    foreach ($post_goods['spec_id'] as $k => $v){
                        $data=[];
                        // Find this based on the submitted goods_spec_id
                        $info =$SpecGoodsModel->getOne($v);
                        $data['refunds_id']=$order_id;
                        $data['shop_id']=$order_info->receiving_shop;
                        $data['spec_id']=$v;
                        $data['goods_id']=$info['goods_id'];
                        $data['refunds_price']=$post_goods['price'][$k];
                        $data['refunds_num']=$post_goods['num'][$k];
                        $data['remarks']=$post_goods['remarks'][$k];;
                        $data['returns_status']=0;
                        $data['total_money']=$post_goods['price'][$k]*$post_goods['num'][$k];
                        $order_money+= $data['total_money'];
                        $goods[]=$data;
                    }
                }

			//Consignee Phone Address Remarks
                $post['order_money']=$order_money;
                $order_info->save($post);
                //Commodity list Commodity ID Price Quantity Remarks
                $PurchaseRefundsListsModel = new PurchaseRefundsListsModel();
                // first delete and save
                $PurchaseRefundsListsModel->where(['refunds_id'=>$order_id])->delete();
                $PurchaseRefundsListsModel->saveAll($goods);
            } catch (Exception $e) {
                // validation failed output error message
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            //add log
            PurchaseRefundsLogModel::operate($this->userId,$order_id,$this->userName." Modified return order:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
        }

        $order_id= input('get.refunds_id');
        $PurchaseRefundsModel = new PurchaseRefundsModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $order_info = $PurchaseRefundsModel->where($map)->find();
        if(!$order_info){
            return json(['code'=>0,'data'=>[],'msg'=>'order does not exist']);
        }

        $PurchaseRefundsListsModel = new PurchaseRefundsListsModel();
        $lists =$PurchaseRefundsListsModel->getLists(['refunds_id'=>$order_id]);;


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
        $order_id= input('refunds_id');
        $PurchaseRefundsModel = new PurchaseRefundsModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $order_info = $PurchaseRefundsModel->where($map)->find();
        if(!$order_info || $order_info->getData('returns_status') != 0){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be operated']);
        }
        $order_info->returns_status=1;
        Db::startTrans();
        try {
        if($order_info->save()){
            $PurchaseRefundsListsModel = new PurchaseRefundsListsModel();
			//Get the list of items
            $goods_lists = $PurchaseRefundsListsModel->where(['refunds_id'=>$order_id])->select();
            $GoodsStock = new GoodsStock();
            $PurchaseReceiptModel = new PurchaseReceiptModel();
            $PurchaseReceiptListsModel = new PurchaseReceiptListsModel();
            foreach ($goods_lists as $v){
                //Reduce store inventory $uid,$goods_id,$spec_id,$shop_id,$num,$act,$type=2,$rem = 'Mall order allocation store'
               if($GoodsStock->decStock($this->userId,$v['goods_id'],$v['spec_id'],$order_info['receiving_shop'],$v['refunds_num'],$v[' refunds_price'],$order_id,4,'Store returns') && $order_info->o_id){
                   //Update the quantity of the goods receipt table
                   $PurchaseReceiptListsModel->where(['receipt_id'=>$order_info->o_id,'spec_id'=>$v['spec_id']])->inc('returns_num', $v['refunds_num'])->update();
               }
            }
			//update purchase table status if created from purchase and
            if($order_info->o_id){
                //If there are still items that have not been returned
                $order_is_ok =$PurchaseReceiptListsModel->where(['receipt_id'=>$order_info->o_id])->where('receipt_num','>',Db::raw('returns_num'))->count();
                $receipt_status =$order_is_ok?1:2;
                //update return status
                $PurchaseReceiptModel->where(['id'=>$order_info->o_id])->update(['returns_status'=>$receipt_status]);
            }
            //modify the order list
            $PurchaseRefundsListsModel->where(['refunds_id'=>$order_id])->update(['returns_status'=>1]);
            //add log
            PurchaseRefundsLogModel::operate($this->userId,$order_id,$this->userName." Submitted return order:".$order_id);
            //Create an account log, only add records, do not participate in other operations
            SupplierMoneyLog::operate($order_info->supplier_id,$order_info->receiving_shop,-$order_info->order_money,2,$order_id);
            Db::commit();
            return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
        } catch (\Exception $e) {
            // rollback transaction
            Db::rollback();
            return json(['code'=>0,'data'=>[],'msg'=>$e->getMessage()]);
        }
    }

    //cancel order
    public function cancelOrder(){
        $order_id= input('refunds_id');
        $PurchaseRefundsModel = new PurchaseRefundsModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $order_info = $PurchaseRefundsModel->where($map)->find();

        if(!$order_info || $order_info->getData('returns_status') != 0 ){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be canceled']);
        }
        $order_info->returns_status=2;
       if($order_info->save()){
			//modify the order list
           $PurchaseRefundsListsModel = new PurchaseRefundsListsModel();
           $PurchaseRefundsListsModel->where(['refunds_id'=>$order_id])->update(['returns_status'=>2]);
           //add log
           PurchaseRefundsLogModel::operate($this->userId,$order_id,$this->userName." canceled return order:".$order_id);
           return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }

    //log
    public function log($refunds_id){
        return json(['code'=>1,'data'=>PurchaseRefundsLogModel::log($refunds_id),'msg'=>'']);
    }

    //Return Statistics
    public function statistical(){
        $shopId = input('shop_id',0);
        if($this->shopId){
            $map[]=['think_purchase_refunds_lists.shop_id','=',$this->shopId];
        }else if ($shopId){
            $map[]=['think_purchase_refunds_lists.shop_id','=',$shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        if($keyWords != ''){
            $map[]=['goods_name', 'like', "%".$keyWords."%"];
        }
        if($startTime){
            $map[]=['think_purchase_refunds_lists.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_purchase_refunds_lists.create_time','<',strtotime($endTime)];
        }
        $map[]=['returns_status','=',1];
        $PurchaseReceiptListsModel = new PurchaseRefundsListsModel();
        $count = $PurchaseReceiptListsModel->group('think_purchase_refunds_lists.goods_id,think_purchase_refunds_lists.shop_id,think_spec_goods.spec_sku')->join('think_spec_goods','think_purchase_refunds_lists.spec_id=think_spec_goods.spec_id','left')->join('think_goods','think_purchase_refunds_lists.goods_id=think_goods.id')->join('think_shop_lists','think_shop_lists.id=think_purchase_refunds_lists.shop_id','left')->where($map)->count();

        $page = input('get.page',1);
        $limits = input('get.limit',10);
        $lists = $PurchaseReceiptListsModel->group('think_purchase_refunds_lists.goods_id,think_purchase_refunds_lists.shop_id,think_spec_goods.spec_sku')->page($page,$limits)->field('refunds_id, refunds_price ,sum(refunds_num) as refunds_num,remarks,think_purchase_refunds_lists.spec_id,spec_key,spec_name,spec_sku,think_purchase_refunds_lists.goods_id,goods_name,goods_sn,think_purchase_refunds_lists.shop_id,think_shop_lists.name as shop_name')->join('think_spec_goods','think_purchase_refunds_lists.spec_id=think_spec_goods.spec_id','left')->join('think_goods','think_purchase_refunds_lists.goods_id=think_goods.id')->join('think_shop_lists','think_shop_lists.id=think_purchase_refunds_lists.shop_id','left')->where($map)->select();

        $shop_map=[];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
        }
        $ShopListsModel = new ShopListsModel();
        $sum = $PurchaseReceiptListsModel->field('sum(refunds_num) as refunds_num ,sum(refunds_price) as refunds_price')->join('think_spec_goods','think_purchase_refunds_lists.spec_id=think_spec_goods.spec_id','left')->join('think_goods','think_purchase_refunds_lists.goods_id=think_goods.id')->join('think_shop_lists','think_shop_lists.id=think_purchase_refunds_lists.shop_id','left')->where($map)->find();
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$count,'lists'=>$lists,'shop_lists'=>$ShopListsModel->getKeyVal($shop_map)],'msg'=>'']);


    }

}