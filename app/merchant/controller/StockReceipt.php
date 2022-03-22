<?php

namespace app\merchant\controller;
use app\merchant\model\GoodsStock;
use app\merchant\model\ShopListsModel;
use app\merchant\model\StockDeliveryListsModel;
use app\merchant\model\StockDeliveryModel;
use app\merchant\model\StockReceiptListsModel;
use app\merchant\model\StockReceiptLogModel;
use app\merchant\model\StockReceiptModel;
use think\Exception;
use think\facade\Db;

class StockReceipt extends Base
{

    //Receipt not completed Ship order
    public function deliveryOrder(){
        $StockDeliveryModel =  new StockDeliveryModel();
        $map = [];
        $map[]=['delivery_status','=',1];
        $map[]=['receipt_status','<>',2];
        if($this->shopId){
            $map[]=['to_shop','=',$this->shopId];
        }
        $Nowpage = input('get.page',1);
        $limits = input('get.limit',15);
        $keywords = input('get.keyWords','');
        if($keywords){
            $map[]=['think_stock_delivery.id|think_shop_lists.name|order_money','like','%'.$keywords.'%'];
        }
       $count = $StockDeliveryModel->join('think_shop_lists','think_shop_lists.id=think_stock_delivery.from_shop','left')->where($map)->count();

       $list = $StockDeliveryModel->field('think_stock_delivery.*,think_shop_lists.name shop_name')->join('think_shop_lists','think_shop_lists.id=think_stock_delivery.from_shop','left')->where($map)->order('id desc')->page($Nowpage,$limits)->select();
      return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list],'msg'=>'']);
    }

    //Home
    public function index(){
        $StockReceiptModel=new StockReceiptModel();

        $map=[];
        if($this->shopId){
            $map[]=['to_shop','=',$this->shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        $receiptStatus = input('get.receiptStatus','');
        if($keyWords != ''){
            //  $map[]=['supplier_name|consignee|phone', 'like', "%".$keyWords."%"];
        }

        if($startTime){
            $map[]=['think_stock_receipt.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_stock_receipt.create_time','<',strtotime($endTime)];
        }
        if($receiptStatus != ''){
            $map[]=['think_stock_receipt.status','=',$receiptStatus];
        }
        $res = $StockReceiptModel->getPaginate($map);
        $sum = $StockReceiptModel->getSumArr($map);
        $lists = $res->toArray();
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data'],'receiptStatus'=>new \ArrayObject($StockReceiptModel->receiptStatus)],'msg'=>'']);
    }

    //Create an order based on the application order
    public function crateReceiptByOrder($delivery_id){

        //read order information
        $StockDeliveryModel = new StockDeliveryModel();
        $map['id'] = $delivery_id;
        $map['delivery_status']=1;
        if($this->shopId){
            $map['to_shop']=$this->shopId;
        }
        $StockDeliveryInfo =$StockDeliveryModel->where($map)->where('receipt_status','<>',2)->find();
        if(!$StockDeliveryInfo){
            return json(['code'=>0,'data'=>[],'msg'=>'The order does not exist']);
        }
        if($StockDeliveryInfo->getData('delivery_status') != 1 || $StockDeliveryInfo->getData('receipt_status') == 2){
            return json(['code'=>0,'data'=>[],'msg'=>'This order cannot create an invoice']);
        }
        //Create Order
        $StockReceiptModel=new StockReceiptModel();
        $r_id = $StockReceiptModel->createOrder($delivery_id,$StockDeliveryInfo['order_money'],$StockDeliveryInfo['to_shop'],$StockDeliveryInfo['from_shop'],'');

        //read cargo table
        $StockDeliveryListsModel = new StockDeliveryListsModel();
        //List the unfinished goods
       $data = $StockDeliveryListsModel->where(['delivery_id'=>$delivery_id])->where("num",">",Db::raw('receipt_quantity'))->select();
        $StockReceiptListsModel = new StockReceiptListsModel();
        if($data){
            $new_data =  [];
            foreach ($data as $v){
                $new_data[]=[
                    'receipt_id'=>$r_id,
                    'shop_id'=>$StockDeliveryInfo['to_shop'],
                    'spec_id'=>$v['spec_id'],
                    'goods_id'=>$v['goods_id'],
                    'price'=>$v['price'],
                    'num'=>$v['num']-$v['receipt_quantity'],
                    'remarks'=>'',
                    'status'=>0,
                    'total_money'=>($v['num']-$v['receipt_quantity'])*$v['price'],
                ];
            }
           //save
            $StockReceiptListsModel->saveAll($new_data);
        }

        $info =$StockReceiptModel->field('think_stock_receipt.*,a.name to_shop_name,b.name from_shop_name')->join('think_shop_lists a','think_stock_receipt.to_shop=a.id','left')-> join('think_shop_lists b','think_stock_receipt.from_shop=b.id','left')->order('think_stock_receipt.id desc')->where(['think_stock_receipt.id'=>$r_id])-> find();
        $lists = $StockReceiptListsModel->getLists(['receipt_id'=>$r_id]);

        //add log
        StockReceiptLogModel::operate($this->userId,$r_id,$this->userName." Create receipt order:".$r_id);
        return json(['code'=>1,'data'=>['info'=>$info,'lists'=>$lists],'msg'=>'']);

    }
    //Receipt list modification
    public function lists(){

        if($this->request->isPost()){
            $order_id= input('post.receipt_id');
            $post_goods= input('post.data');
            $post= input('post.');
            $order_money=0;
            $order_num=0;
            $StockReceiptModel=new StockReceiptModel();
            $StockReceiptListsModel = new StockReceiptListsModel();
            try {
                $map=['id'=>$order_id];
                if($this->shopId){
                    $map['to_shop']=$this->shopId;
                }
                $order_info = $StockReceiptModel->where($map)->find();

                if(!$order_info || $order_info->getData('status') != 0){
                    return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be operated']);
                }

                if($post_goods){
                    $spec_price =  $StockReceiptListsModel->where(['receipt_id'=>$order_id])->column('price','spec_id');
                    foreach ($post_goods['spec_id'] as $k => $v){
                        $num = $post_goods['num'][$k];
                        $remarks = $post_goods['remarks'][$k];
                        $total_money = $spec_price[$v]*$post_goods['num'][$k];
                        $StockReceiptListsModel->where(['receipt_id'=>$order_id,'spec_id'=>$v])->update(['num'=>$num,'remarks'=>$remarks,'total_money'=>$total_money]);
                        $order_money+= $total_money;
                        $order_num+= $post_goods['num'][$k];
                    }
                }
                //modify the total
                $post['order_money']=$order_money;
                $post['order_num']=$order_num;
                $order_info->save($post);
            } catch (Exception $e) {
                // validation failed output error message
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            //add log
            StockReceiptLogModel::operate($this->userId,$order_id,$this->userName." Modified receipt order:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
        }
        $order_id= input('get.receipt_id');

        $StockReceiptModel=new StockReceiptModel();
        $StockReceiptListsModel = new StockReceiptListsModel();
        $info =$StockReceiptModel->field('think_stock_receipt.*,a.name to_shop_name,b.name from_shop_name')->join('think_shop_lists a','think_stock_receipt.to_shop=a.id','left')->join('think_shop_lists b','think_stock_receipt.from_shop=b.id','left')->order('think_stock_receipt.id desc')->where(['think_stock_receipt.id'=>$order_id])->find();
        $lists = $StockReceiptListsModel->getLists(['receipt_id'=>$order_id]);


        return json(['code'=>1,'data'=>['info'=>$info,'lists'=>$lists],'msg'=>'']);

    }
    //Sure
    public function complete(){
        $order_id= input('receipt_id');
        $StockReceiptModel=new StockReceiptModel();


        $map=['id'=>$order_id];
        if($this->shopId){
            $map['to_shop']=$this->shopId;
        }
        $order_info = $StockReceiptModel->where($map)->find();

        if(!$order_info || $order_info->getData('status') != 0){
            return json(['code'=>0,'data'=>[],'msg'=>'current state cannot be operated']);
        }
        $order_info->status=1;
        Db::startTrans();
        try {
        if($order_info->save()){
            $StockReceiptListsModel = new StockReceiptListsModel();
            //Get the list of items
            $goods_lists = $StockReceiptListsModel->where(['receipt_id'=>$order_id])->select();

            //First judge whether this has been received or the quantity is correct and cannot exceed
           $StockDeliveryListsModel = new StockDeliveryListsModel();
           $DeliveryNum =$StockDeliveryListsModel->where(['delivery_id'=>$order_info->o_id])->column('num,receipt_quantity','spec_id');
            $GoodsStock = new GoodsStock();
            foreach ($goods_lists as $v){
                if($v->num <= 0){
                    $StockReceiptListsModel->where(['receipt_id'=>$order_id,'id'=>$v->id])->delete();
                    continue;
                    throw new Exception('goods id:'.$v->goods_id.'The quantity received cannot be less than or equal to 0', 500);
                }
                if(!isset($DeliveryNum[$v->spec_id])){
                    throw new Exception('Goods id:'.$v->goods_id.'Not within the scope of delivery', 500);
                };
                if($DeliveryNum[$v->spec_id]['num'] < $DeliveryNum[$v->spec_id]['receipt_quantity']+$v->num){
                    throw new Exception('Product id:'.$v->goods_id.'The quantity received exceeds the quantity delivered', 500);
                }

               //add stock
               if($GoodsStock->addStock($this->userId,$order_id,$order_info->to_shop,$v->goods_id,$v->spec_id,$v->num,$v->price,8,' Transfer and receive goods') && $order_info->o_id){
                   //Update the purchase quantity in the purchase item table
                   $StockDeliveryListsModel->where(['delivery_id'=>$order_info->o_id,'spec_id'=>$v->spec_id])->inc('receipt_quantity', $v->num)->update();
               }
            }
            //update purchase table status if created from purchase and
            //If there are still items that have not been put into the warehouse
            $order_is_ok =$StockDeliveryListsModel->where(['delivery_id'=>$order_info->o_id])->where('num','>',Db::raw('receipt_quantity'))->count();
            $receipt_status =$order_is_ok?1:2;
            $StockDeliveryModel = new StockDeliveryModel();
            $StockDeliveryModel->where(['id'=>$order_info->o_id])->update(['receipt_status'=>$receipt_status]);
            //modify the order list
            $StockReceiptListsModel->where(['receipt_id'=>$order_id])->update(['status'=>1]);
            //If the receipt here is completed, the other unsubmitted orders will become canceled
            if($receipt_status == 2){
                $StockReceiptListsModel->where(['receipt_id'=>$order_info->o_id,'status'=>0])->update(['status'=>2]);
            }
            //add log
            StockReceiptLogModel::operate($this->userId,$order_id,$this->userName." Submitted receipt order:".$order_id);
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
    public function cancel(){
        $order_id= input('receipt_id');
        $StockReceiptModel = new StockReceiptModel();
        $map=['id'=>$order_id];
        if($this->shopId){
            $map['to_shop']=$this->shopId;
        }
        $order_info = $StockReceiptModel->where($map)->find();

        if(!$order_info || $order_info->getData('status') != 0 ){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be cancelled']);
        }
        $order_info->status=2;
       if($order_info->save()){
           //modify the order list
           $StockReceiptListsModel = new StockReceiptListsModel();
           $StockReceiptListsModel->where(['receipt_id'=>$order_id])->update(['status'=>2]);
           //add log
           StockReceiptLogModel::operate($this->userId,$order_id,$this->userName." canceled the receipt order:".$order_id);
           return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }
    //log
    public function log($receipt_id){
        return json(['code'=>1,'data'=>StockReceiptLogModel::log($receipt_id),'msg'=>'']);
    }

    // Receipt statistics
    public function statistical(){
        $shopId = input('shop_id',0);
        if($this->shopId){
            $map[]=['think_stock_receipt_lists.shop_id','=',$this->shopId];
        }else if ($shopId){
            $map[]=['think_stock_receipt_lists.shop_id','=',$shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        if($keyWords != ''){
            $map[]=['goods_name', 'like', "%".$keyWords."%"];
        }
        if($startTime){
            $map[]=['think_stock_receipt_lists.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_stock_receipt_lists.create_time','<',strtotime($endTime)];
        }
        $map[]=['think_stock_receipt_lists.status','=',1];

        $PurchaseReceiptListsModel =  new StockReceiptListsModel();
        $count = $PurchaseReceiptListsModel
            ->join('think_spec_goods','think_stock_receipt_lists.spec_id=think_spec_goods.spec_id','left')
            ->join('think_goods','think_stock_receipt_lists.goods_id=think_goods.id','left')
            ->join('think_shop_lists','think_shop_lists.id=think_stock_receipt_lists.shop_id','left')
            ->group('think_stock_receipt_lists.goods_id,think_stock_receipt_lists.shop_id,think_spec_goods.spec_sku')
            ->where($map)
            ->count();

        $page = input('get.page',1);
        $limits = input('get.limit',10);
        $lists = $PurchaseReceiptListsModel
            ->field('receipt_id,sum(price*num) as price,sum(num) as num,remarks,think_stock_receipt_lists.spec_id,spec_key,spec_name,spec_sku,think_stock_receipt_lists.goods_id,goods_name,goods_sn,think_stock_receipt_lists.shop_id,think_shop_lists.name as shop_name')
            ->join('think_spec_goods','think_stock_receipt_lists.spec_id=think_spec_goods.spec_id','left')
            ->join('think_goods','think_stock_receipt_lists.goods_id=think_goods.id','left')
            ->join('think_shop_lists','think_shop_lists.id=think_stock_receipt_lists.shop_id','left')
            ->group('think_stock_receipt_lists.goods_id,think_stock_receipt_lists.shop_id,think_spec_goods.spec_sku')
            ->where($map)
            ->page($page,$limits)
            ->select();
        $shop_map=[];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
        }
        $sum = $PurchaseReceiptListsModel->field('sum(price*num) as price,sum(num) as num') ->join('think_spec_goods','think_stock_receipt_lists.spec_id=think_spec_goods.spec_id','left')
            ->join('think_goods','think_stock_receipt_lists.goods_id=think_goods.id','left')
            ->join('think_shop_lists','think_shop_lists.id=think_stock_receipt_lists.shop_id','left')
            ->where($map)->find();
        $ShopListsModel = new ShopListsModel();
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$count,'lists'=>$lists,'shop_lists'=>$ShopListsModel->getKeyVal($shop_map)],'msg'=>'']);

    }


}