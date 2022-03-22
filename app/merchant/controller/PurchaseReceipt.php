<?php

namespace app\merchant\controller;
use app\merchant\model\Goods as GoodsModel;
use app\merchant\model\GoodsStock;
use app\merchant\model\GoodsSupplierModel;
use app\merchant\model\PurchaseOrderListsModel;
use app\merchant\model\PurchaseOrderModel;
use app\merchant\model\PurchaseOrderLogModel;
use app\merchant\model\PurchaseReceiptListsModel;
use app\merchant\model\PurchaseReceiptLogModel;
use app\merchant\model\PurchaseReceiptModel;
use app\merchant\model\ShopListsModel;
use app\merchant\model\SpecGoodsModel;
use app\merchant\model\SupplierMoneyLog;
use app\common\service\StockCommon;
use think\Exception;
use think\facade\Db;

class PurchaseReceipt extends Base
{
    //Orders that have not been received
    public function purchaseOrder(){
      $PurchaseOrder =  new PurchaseOrderModel();
      $map = [];
      $map[]=['order_status','=',1];
        if($this->shopId){
            $map[]=['receiving_shop','=',$this->shopId];
        }
        $Nowpage = input('get.page',1);
        $limits = input('get.limit',15);
        $keywords = input('get.keywords','');
        if($keywords){
            $map[]=['id|supplier_id|consignee|phone','like','%'.$keywords.'%'];
        }
       $count = $PurchaseOrder->where($map)->where('receipt_status','<>',2)->count();

       $list = $PurchaseOrder->where($map)->where('receipt_status','<>',2)->order('id desc')->page($Nowpage,$limits)->select();
        foreach ($list as $v){
            $v->supplier_name=$v->getSupplier();
        }
      return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list],'msg'=>'']);
    }

    //Create order based on purchase order
    public function crateReceiptByOrder($order_id){

        //read order information
        $PurchaseOrderModel = new PurchaseOrderModel();
        $map['id'] = $order_id;
        $map['order_status']=1;
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $PurchaseOrderInfo =$PurchaseOrderModel->where($map)->find();
        if(!$PurchaseOrderInfo){
            return json(['code'=>0,'data'=>[],'msg'=>'The purchase order does not exist']);
        }
        if($PurchaseOrderInfo->getData('order_status') != 1 || $PurchaseOrderInfo->getData('receipt_status') == 2){
            return json(['code'=>0,'data'=>[],'msg'=>'The purchase order cannot create a receipt']);
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
       //Address information
        $addr = new \app\common\model\Address();
        $address = $addr->getAddr($shop->province,$shop->city,$shop->area);

        //Create Order
        $PurchaseReceiptModel = new PurchaseReceiptModel();
        $r_id = $PurchaseReceiptModel->createOrder($order_id,$PurchaseOrderInfo['supplier_id'],$PurchaseOrderInfo['order_money'],$shop->forUser->real_name,$shop->phone,$address.' '.$ shop->address,$shop_id);

        //read cargo table
        $PurchaseOrderListsModel = new PurchaseOrderListsModel();
        //List the unfinished goods
       $data = $PurchaseOrderListsModel->where(['order_id'=>$order_id])->where("purchase_num",">",Db::raw('receipt_quantity'))->select();
        $PurchaseReceiptListsModel = new PurchaseReceiptListsModel();
        if($data){
            $new_data =  [];
            foreach ($data as $v){
                $new_data[]=[
                    'receipt_id'=>$r_id,
                    'spec_id'=>$v['spec_id'],
                    'goods_id'=>$v['goods_id'],
                    'supplier_id'=>$v['supplier_id'],
                    'receipt_price'=>$v['purchase_price'],
                    'receipt_num'=>$v['purchase_num']-$v['receipt_quantity'],
                    'remarks'=>'',
                    'receipt_status'=>0,
                    'total_money'=>$v['total_money'],
                    'return_num'=>0,
                ];
            }

            //save
            $PurchaseReceiptListsModel->saveAll($new_data);
        }
        //get supplier information
        $GoodsSupplierModel = new GoodsSupplierModel();
        $supplier = $GoodsSupplierModel->getOne($PurchaseReceiptModel->supplier_id);
        if(!$supplier){
            return json(['code'=>0,'data'=>[],'msg'=>'supplier does not exist']);
        }
        $PurchaseReceiptModel->supplier_name=$supplier->supplier_name;
        $PurchaseReceiptModel->shop_name=$shop->name;

        $lists = $PurchaseReceiptListsModel->getLists(['receipt_id'=>$r_id]);

        //add log
        PurchaseReceiptLogModel::operate($this->userId,$r_id,$this->userName." Create receipt order:".$r_id);
        return json(['code'=>1,'data'=>['info'=>$PurchaseReceiptModel,'lists'=>$lists],'msg'=>'']);

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
        //Address information
        $addr = new \app\common\model\Address();
        $address = $addr->getAddr($shop->province,$shop->city,$shop->area);

        //Create Order
        $PurchaseReceiptModel = new PurchaseReceiptModel();
        //$o_id,$supplier_id,$order_money,$consignee,$phone,$addr,$receiving_shop
        $r_id = $PurchaseReceiptModel->createOrder(0,$supplier_id,0,$shop->forUser->real_name,$shop->phone,$address.' '.$shop->address,$shop_id);

        $PurchaseReceiptModel->supplier_name=$supplier->supplier_name;
        $PurchaseReceiptModel->shop_name=$shop->name;
        //add log
        PurchaseReceiptLogModel::operate($this->userId,$r_id,$this->userName." Create receipt order:".$r_id);
        return json(['code'=>1,'data'=>['info'=>$PurchaseReceiptModel,'lists'=>[]],'msg'=>'']);

    }


    // file import
    public function import(){
        $data = StockCommon::import();
        $supplier_id = input('supplier_id',0);
        $d = $data['data'];
        $SpecGoodsModel = new SpecGoodsModel();
        $lists=[];
        $GoodsModel = new GoodsModel();
        foreach ($d as $k => $v){
            $map=[];
            $info = $SpecGoodsModel->field('spec_id,spec_key,spec_name,spec_sku,goods_id,cost_price')->where('spec_sku',$k)->find();
            if($info){
                if($supplier_id){
                    $map[] = ['supplier_id','=',$supplier_id];
                }
                $map[] = ['id','=',$info->goods_id];
                $res = $GoodsModel->field('id,goods_sn,goods_name,spec_type,cost_price')->where($map)->find();
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
        $PurchaseOrderModel = new PurchaseReceiptModel();
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
            $map[]=['think_purchase_refunds.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_purchase_refunds.create_time','<',strtotime($endTime)];
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
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data'],'returnsStatus'=>$PurchaseOrderModel->returnsStatus,'receiptStatus'=>$PurchaseOrderModel->receiptStatus],'msg'=>'']);

    }


    //save dingd
    public function saveOrder(){

        if($this->request->isPost()){
            $order_id= input('post.receipt_id');
            $post_goods= input('post.goods');
            $post= input('post.');

            try {
                $PurchaseOrderModel = new PurchaseReceiptModel();
                $map=['id'=>$order_id];
                if($this->shopId){
                    $map['receiving_shop']=$this->shopId;
                }
                $order_info = $PurchaseOrderModel->where($map)->find();

                if(!$order_info || $order_info->getData('receipt_status') != 0){
                    return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be operated']);
                }
                $goods=[];
                $order_money=0;
                if($post_goods){
                    $SpecGoodsModel= new SpecGoodsModel();
                    foreach ($post_goods['spec_id'] as $k => $v){
                        $data=[];
                        //Find this based on the submitted goods_spec_id
                        $info =$SpecGoodsModel->getOne($v);
                        $data['receipt_id']=$order_id;
                        $data['shop_id']=$order_info->receiving_shop;
                        $data['spec_id']=$v;
                        $data['goods_id']=$info['goods_id'];
                        $data['receipt_price']=$post_goods['price'][$k];
                        $data['receipt_num']=$post_goods['num'][$k];
                        $data['remarks']=$post_goods['remarks'][$k];;
                        $data['receipt_status']=0;
                        $data['total_money']=$post_goods['price'][$k]*$post_goods['num'][$k];
                        $data['return_num']=0;
                        $order_money+= $data['total_money'];
                        $goods[]=$data;
                    }
                }

                //Consignee Phone Address Remarks
                $post['order_money']=$order_money;
                $order_info->save($post);
                //Commodity list Commodity ID Price Quantity Remarks
                $PurchaseReceiptListsModel = new PurchaseReceiptListsModel();
                // first delete and save
                $PurchaseReceiptListsModel->where(['receipt_id'=>$order_id])->delete();
                $PurchaseReceiptListsModel->saveAll($goods);
            } catch (Exception $e) {
                // validation failed output error message
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            //add log
            PurchaseReceiptLogModel::operate($this->userId,$order_id,$this->userName." Modified receipt order:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
        }

        $order_id= input('get.receipt_id');
        $PurchaseReceiptModel = new PurchaseReceiptModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $order_info = $PurchaseReceiptModel->where($map)->find();
        if(!$order_info){
          return json(['code'=>0,'data'=>[],'msg'=>'order does not exist']);
        }

        $PurchaseReceiptListsModel = new PurchaseReceiptListsModel();
        $lists =$PurchaseReceiptListsModel->getLists(['receipt_id'=>$order_id]);;


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
        $order_id= input('receipt_id');
        $PurchaseReceiptModel = new PurchaseReceiptModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $order_info = $PurchaseReceiptModel->where($map)->find();

        if(!$order_info || $order_info->getData('receipt_status') != 0){
            return json(['code'=>0,'data'=>[],'msg'=>'current state cannot be operated']);
        }
        $order_info->receipt_status=1;
        if($order_info->save()){
            $PurchaseReceiptListsModel = new PurchaseReceiptListsModel();
            //Get the list of items
            $goods_lists = $PurchaseReceiptListsModel->where(['receipt_id'=>$order_id])->select();
            $GoodsStock = new GoodsStock();
            $PurchaseOrderModel = new PurchaseOrderModel();
            $PurchaseOrderListsModel = new PurchaseOrderListsModel();
            foreach ($goods_lists as $v){
                //Add to store inventory
               if($GoodsStock->addStock($this->userId,$order_id,$order_info->receiving_shop,$v->goods_id,$v->spec_id,$v->receipt_num,$v->receipt_price) && $order_info ->o_id){
                   //Update the purchase quantity in the purchase item table
                   $PurchaseOrderListsModel->where(['order_id'=>$order_info->o_id,'spec_id'=>$v->spec_id])->inc('receipt_quantity', $v->receipt_num)->update();

               }
            }
            //update purchase table status if created from purchase and
            if($order_info->o_id){
                //If there are still items that have not been put into the warehouse
                $order_is_ok =$PurchaseOrderListsModel->where(['order_id'=>$order_info->o_id])->where('purchase_num','>',Db::raw('receipt_quantity'))->count();
                $receipt_status =$order_is_ok?1:2;
                $PurchaseOrderModel->where(['id'=>$order_info->o_id])->update(['receipt_status'=>$receipt_status]);
            }
            //modify the order list
            $PurchaseReceiptListsModel->where(['receipt_id'=>$order_id])->update(['receipt_status'=>1]);
            //add log
            PurchaseReceiptLogModel::operate($this->userId,$order_id,$this->userName." Submitted receipt order:".$order_id);

            //Create an account log, only add records, do not participate in other operations
            SupplierMoneyLog::operate($order_info->supplier_id,$order_info->receiving_shop,$order_info->order_money,1,$order_id);

            return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }

    //cancel order
    public function cancelOrder(){
        $order_id= input('receipt_id');
        $PurchaseReceiptModel = new PurchaseReceiptModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['receiving_shop']=$this->shopId;
        }
        $order_info = $PurchaseReceiptModel->where($map)->find();

        if(!$order_info || $order_info->getData('receipt_status') != 0 ){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be cancelled']);
        }
        $order_info->receipt_status=2;
       if($order_info->save()){
           //modify the order list
           $PurchaseOrderListsModel = new PurchaseReceiptListsModel();
           $PurchaseOrderListsModel->where(['receipt_id'=>$order_id])->update(['receipt_status'=>2]);
           //add log
           PurchaseReceiptLogModel::operate($this->userId,$order_id,$this->userName." canceled the receipt order:".$order_id);
           return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }

   //log
       public function log($order_id){
           return json(['code'=>1,'data'=>PurchaseOrderLogModel::log($order_id),'msg'=>'']);
       }
   
       // Receipt statistics
    public function statistical(){
        $shopId = input('shop_id',0);
        if($this->shopId){
            $map[]=['think_purchase_receipt_lists.shop_id','=',$this->shopId];
        }else if ($shopId){
            $map[]=['think_purchase_receipt_lists.shop_id','=',$shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        if($keyWords != ''){
            $map[]=['goods_name', 'like', "%".$keyWords."%"];
        }
        if($startTime){
            $map[]=['think_purchase_receipt_lists.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_purchase_receipt_lists.create_time','<',strtotime($endTime)];
        }
        $map[]=['receipt_status','=',1];
        $PurchaseReceiptListsModel = new PurchaseReceiptListsModel();
        $count = $PurchaseReceiptListsModel->group('think_purchase_receipt_lists.goods_id,think_purchase_receipt_lists.shop_id,think_spec_goods.spec_sku')->join('think_spec_goods','think_purchase_receipt_lists.spec_id=think_spec_goods.spec_id','left')->join('think_goods','think_purchase_receipt_lists.goods_id=think_goods.id')->join('think_shop_lists','think_shop_lists.id=think_purchase_receipt_lists.shop_id','left')->where($map)->count();

        $page = input('get.page',1);
        $limits = input('get.limit',10);
        $lists = $PurchaseReceiptListsModel->page($page,$limits)->field('receipt_id,sum(receipt_price) as receipt_price,receipt_num,remarks,think_purchase_receipt_lists.spec_id,spec_key,spec_name,spec_sku,think_purchase_receipt_lists.goods_id,goods_name,goods_sn,think_purchase_receipt_lists.shop_id,think_shop_lists.name as shop_name')->group('think_purchase_receipt_lists.goods_id,think_purchase_receipt_lists.shop_id,think_spec_goods.spec_sku')->join('think_spec_goods','think_purchase_receipt_lists.spec_id=think_spec_goods.spec_id','left')->join('think_goods','think_purchase_receipt_lists.goods_id=think_goods.id')->join('think_shop_lists','think_shop_lists.id=think_purchase_receipt_lists.shop_id','left')->where($map)->select();
        $sum = $PurchaseReceiptListsModel->field('sum(receipt_price) as receipt_price,sum(receipt_num) as receipt_num')->join('think_spec_goods','think_purchase_receipt_lists.spec_id=think_spec_goods.spec_id','left')->join('think_goods','think_purchase_receipt_lists.goods_id=think_goods.id')->join('think_shop_lists','think_shop_lists.id=think_purchase_receipt_lists.shop_id','left')->where($map)->find();


        $shop_map=[];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
        }
        $ShopListsModel = new ShopListsModel();
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$count,'lists'=>$lists,'shop_lists'=>$ShopListsModel->getKeyVal($shop_map)],'msg'=>'']);


    }


}