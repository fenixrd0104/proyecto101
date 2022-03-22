<?php

namespace app\merchant\controller;
use app\merchant\model\Goods as GoodsModel;
use app\merchant\model\GoodsStock;
use app\merchant\model\PurchaseReceiptLogModel;
use app\merchant\model\ShopListsModel;
use app\merchant\model\SpecGoodsModel;
use app\merchant\model\StockApplyListsModel;
use app\merchant\model\StockApplyModel;
use app\merchant\model\StockDeliveryListsModel;
use app\merchant\model\StockDeliveryLogModel;
use app\merchant\model\StockDeliveryModel;
use app\common\service\StockCommon;
use think\Exception;
use think\facade\Db;

class StockDelivery extends Base
{

    //Unfinished Apply for transfer order
    public function applyOrder(){
      $StockApplyModel =  new StockApplyModel();
      $map = [];
      $map[]=['order_status','=',4];
        if($this->shopId){
            $map[]=['from_shop','=',$this->shopId];
        }
        $Nowpage = input('get.page',1);
        $limits = input('get.limit',15);
        $keywords = input('get.keyWords','');
        if($keywords){
            $map[]=['think_stock_apply.id|think_shop_lists.name|to_remarks|from_remarks','like','%'.$keywords.'%'];
        }
       $count = $StockApplyModel->join('think_shop_lists','think_shop_lists.id=think_stock_apply.to_shop','left')->where($map)->where('delivery_status','<>',3)->count();

       $list = $StockApplyModel->field('think_stock_apply.*,think_shop_lists.name shop_name')->join('think_shop_lists','think_shop_lists.id=think_stock_apply.to_shop','left')->where($map)->where('delivery_status','<>',3)->order('id desc')->page($Nowpage,$limits)->select();

      return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list],'msg'=>'']);
    }

    public function index(){
        $StockDeliveryModel= new StockDeliveryModel();

        $map=[];
        if($this->shopId){
            $map[]=['from_shop','=',$this->shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        $deliveryStatus = input('get.deliveryStatus','');
        $receiptStatus = input('get.receiptStatus','');
        if($keyWords != ''){
            //$map[]=['think_shop_lists.shop_name|id|phone', 'like', "%".$keyWords."%"];
            $map[]=['a.name|b.name|think_stock_delivery.id|think_stock_delivery.id|think_stock_delivery.o_id|think_stock_delivery.remarks', 'like', "%".$keyWords."%"];
        }

        if($startTime){
            $map[]=['think_stock_delivery.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_stock_delivery.create_time','<',strtotime($endTime)];
        }
        if($deliveryStatus != ''){
            $map[]=['delivery_status','=',$deliveryStatus];
        }
        if($receiptStatus != ''){
            $map[]=['receipt_status','=',$receiptStatus];
        }
        $res = $StockDeliveryModel->getPaginate($map);
        $lists = $res->toArray();
        $sum = $StockDeliveryModel->getSumArr($map);
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data'],'returnsStatus'=>$StockDeliveryModel->deliveryStatus,'receiptStatus'=>$StockDeliveryModel->receiptStatus],'msg'=>'']);
    }

    //Create an order based on the application order
    public function crateDeliveryByOrder($order_id){

        //read order information
        $StockApplyModel = new StockApplyModel();
        $map['id'] = $order_id;
        $map['order_status']=4;
        if($this->shopId){
            $map['from_shop']=$this->shopId;
        }
        $StockApplyInfo =$StockApplyModel->where($map)->where('delivery_status','<>',3)->find();

        if(!$StockApplyInfo){
            return json(['code'=>0,'data'=>[],'msg'=>'The order does not exist']);
        }
        if($StockApplyInfo->getData('order_status') != 4 || $StockApplyInfo->getData('delivery_status') == 3){
            return json(['code'=>0,'data'=>[],'msg'=>'This order cannot create an invoice']);
        }

        //Create Order
        $StockDeliveryModel= new StockDeliveryModel();
        $r_id = $StockDeliveryModel->createOrder($order_id,$StockApplyInfo['order_money'],$StockApplyInfo['to_shop'],$StockApplyInfo['from_shop'],'');
        //read cargo table
        $StockApplyListsModel = new StockApplyListsModel();
        //List the unfinished goods
       $data = $StockApplyListsModel->where(['order_id'=>$order_id])->where("num",">",Db::raw('delivery_quantity'))->select();
        $StockDeliveryListsModel = new StockDeliveryListsModel();
       if($data){
            $new_data =  [];
            foreach ($data as $v){
                $new_data[]=[
                    'delivery_id'=>$r_id,
                    'shop_id'=>$StockApplyInfo['from_shop'],
                    'spec_id'=>$v['spec_id'],
                    'goods_id'=>$v['goods_id'],
                    'price'=>$v['price'],
                    'num'=>$v['num']-$v['delivery_quantity'],
                    'remarks'=>'',
                    'delivery_status'=>0,
                    'total_money'=>sprintf("%.2f",$v['num']*($v['num']-$v['delivery_quantity'])),
                ];
            }
            //save
            $StockDeliveryListsModel->saveAll($new_data);
        }


        $info =$StockDeliveryModel->field('think_stock_delivery.*,a.name to_shop_name,b.name from_shop_name')->join('think_shop_lists a','think_stock_delivery.to_shop=a.id','left')-> join('think_shop_lists b','think_stock_delivery.from_shop=b.id','left')->order('think_stock_delivery.id desc')->where(['think_stock_delivery.id'=>$r_id])-> find();
        $lists = $StockDeliveryListsModel->getLists(['delivery_id'=>$r_id]);
        //add log
        StockDeliveryLogModel::operate($this->userId,$r_id,$this->userName." Create delivery order:".$r_id);
        return json(['code'=>1,'data'=>['info'=>$info,'lists'=>$lists],'msg'=>'']);

    }

    // create an order based on the store
    public function crateReceiptByNew($to_shop){
        $shop_id =$this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $shop_id_arr = StockCommon::getStockTransferShopId($shop_id);
        if(!$shop_id_arr['code']){
            return json($shop_id_arr);
        }

        if(!in_array($to_shop,$shop_id_arr['data'])){
            return json(['code'=>0,'data'=>[],'msg'=>'Cannot apply for this store to transfer goods']);
        }
        //Create Order
        $StockDeliveryModel= new StockDeliveryModel();
        $r_id = $StockDeliveryModel->createOrder(0,0,$to_shop,$shop_id,'');
        //add log
        PurchaseReceiptLogModel::operate($this->userId,$r_id,$this->userName."Create shipping order:".$r_id);

        $info =$StockDeliveryModel->field('think_stock_delivery.*,a.name to_shop_name,b.name from_shop_name')->join('think_shop_lists a','think_stock_delivery.to_shop=a.id','left')-> join('think_shop_lists b','think_stock_delivery.from_shop=b.id','left')->order('think_stock_delivery.id desc')->where(['think_stock_delivery.id'=>$r_id])-> find();
        return json(['code'=>1,'data'=>['info'=>$info,'lists'=>[]],'msg'=>'']);

    }
    //save dingd
    public function lists(){

        if($this->request->isPost()){
            $order_id= input('post.delivery_id');
            $post_goods= input('post.data');
            $post= input('post.');
            $goods=[];
            $order_money=0;
            $order_num=0;

            try {
                $StockDeliveryModel= new StockDeliveryModel();
                $map=['id'=>$order_id];
                if($this->shopId){
                    $map['from_shop']=$this->shopId;
                }
                $order_info = $StockDeliveryModel->where($map)->find();

                if(!$order_info || $order_info->getData('delivery_status') != 0){
                    return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be operated']);
                }
                if($post_goods){
                    $SpecGoodsModel= new SpecGoodsModel();
                    foreach ($post_goods['spec_id'] as $k => $v){
                        $data=[];
                        //Find this based on the submitted goods_spec_id
                        $info =$SpecGoodsModel->getOne($v);
                        $data['delivery_id']=$order_id;
                        $data['shop_id']=$order_info->from_shop;
                        $data['spec_id']=$v;
                        $data['goods_id']=$info['goods_id'];
                        $data['price']=$post_goods['price'][$k];
                        $data['num']=$post_goods['num'][$k];
                        $data['remarks']=$post_goods['remarks'][$k];;
                        $data['delivery_status']=0;
                        $data['total_money']=$post_goods['price'][$k]*$post_goods['num'][$k];
                        $order_money+= $data['total_money'];
                        $order_num+= $data['num'];
                        $goods[]=$data;
                    }
                }
               //Consignee Phone Address Remarks
                $post['order_money']=$order_money;
                $post['order_num']=$order_num;
                $order_info->save($post);
                //Commodity list Commodity ID Price Quantity Remarks
                $StockDeliveryListsModel = new StockDeliveryListsModel();
                // first delete and save
                $StockDeliveryListsModel->where(['delivery_id'=>$order_id])->delete();
                $StockDeliveryListsModel->saveAll($goods);
            } catch (Exception $e) {
                // validation failed output error message
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            //add log
            StockDeliveryLogModel::operate($this->userId,$order_id,$this->userName." Modified delivery order:".$order_id);
            return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
        }

        $order_id= input('get.delivery_id');
        $StockDeliveryModel= new StockDeliveryModel();
        $StockDeliveryListsModel = new StockDeliveryListsModel();
        $info =$StockDeliveryModel->field('think_stock_delivery.*,a.name to_shop_name,b.name from_shop_name')->join('think_shop_lists a','think_stock_delivery.to_shop=a.id','left')->join('think_shop_lists b','think_stock_delivery.from_shop=b.id','left')->order('think_stock_delivery.id desc')->where(['think_stock_delivery.id'=>$order_id])->find();
        $lists = $StockDeliveryListsModel->getLists(['delivery_id'=>$order_id]);
        return json(['code'=>1,'data'=>['info'=>$info,'lists'=>$lists],'msg'=>'']);

    }

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

    //Sure
    public function complete(){
        $order_id= input('delivery_id');
        $StockDeliveryModel= new StockDeliveryModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['from_shop']=$this->shopId;
        }
        $order_info = $StockDeliveryModel->where($map)->find();

        if(!$order_info || $order_info->getData('delivery_status') != 0){
            return json(['code'=>0,'data'=>[],'msg'=>'current state cannot be operated']);
        }
        $order_info->delivery_status=1;
        Db::startTrans();
        try {
            if($order_info->save()){
                $StockDeliveryListsModel = new StockDeliveryListsModel();
                //Get the list of items
                $goods_lists = $StockDeliveryListsModel->where(['delivery_id'=>$order_id])->select();
                $GoodsStock = new GoodsStock();
                $StockApplyModel = new StockApplyModel();
                $StockApplyListsModel = new StockApplyListsModel();
                foreach ($goods_lists as $v){
                    //Reduce store inventory $uid,$goods_id,$spec_id,$shop_id,$num,$act,$type=2,$rem = 'Mall order allocation store'
                   if($GoodsStock->decStock($this->userId,$v->goods_id,$v->spec_id,$order_info->from_shop,$v->num,$v->price,$order_id,7,' Adjustment and delivery') && $order_info->o_id){
                       //Update the purchase quantity in the purchase item table
                       $StockApplyListsModel->where(['order_id'=>$order_info->o_id,'spec_id'=>$v->spec_id])->inc('delivery_quantity', $v->num)->update();
                   }
                }
                //update purchase table status if created from purchase and
                if($order_info->o_id){
                    //If there are still items that have not been put into the warehouse
                    $order_is_ok =$StockApplyListsModel->where(['order_id'=>$order_info->o_id])->where('num','>',Db::raw('delivery_quantity'))->count();
                    $receipt_status =$order_is_ok?2:3;
                    $StockApplyModel->where(['id'=>$order_info->o_id])->update(['delivery_status'=>$receipt_status]);
                }
                //modify the order list
                $StockDeliveryListsModel->where(['delivery_id'=>$order_id])->update(['delivery_status'=>1]);
                //add log
                StockDeliveryLogModel::operate($this->userId,$order_id,$this->userName." Submitted delivery order:".$order_id);
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
        $order_id= input('delivery_id');
        $StockDeliveryModel= new StockDeliveryModel();

        $map=['id'=>$order_id];
        if($this->shopId){
            $map['from_shop']=$this->shopId;
        }
        $order_info = $StockDeliveryModel->where($map)->find();

        if(!$order_info || $order_info->getData('delivery_status') != 0 ){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be cancelled']);
        }
        $order_info->delivery_status=2;
       if($order_info->save()){
           //modify the order list
           $StockDeliveryListsModel = new StockDeliveryListsModel();
           $StockDeliveryListsModel->where(['delivery_id'=>$order_id])->update(['delivery_status'=>2]);
           //add log
           StockDeliveryLogModel::operate($this->userId,$order_id,$this->userName." Canceled delivery order:".$order_id);
           return json(['code'=>1,'data'=>[],'msg'=>'operation successful']);
        }
    }

    //log
    public function log($delivery_id){
        return json(['code'=>1,'data'=>StockDeliveryLogModel::log($delivery_id),'msg'=>'']);
    }

    //delivery statistics
    public function statistical(){
        $shopId = input('shop_id',0);
        if($this->shopId){
            $map[]=['think_stock_delivery_lists.shop_id','=',$this->shopId];
        }else if ($shopId){
            $map[]=['think_stock_delivery_lists.shop_id','=',$shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        if($keyWords != ''){
            $map[]=['goods_name', 'like', "%".$keyWords."%"];
        }
        if($startTime){
            $map[]=['think_stock_delivery_lists.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_stock_delivery_lists.create_time','<',strtotime($endTime)];
        }
        $map[]=['delivery_status','=',1];

        $PurchaseReceiptListsModel =  new StockDeliveryListsModel();
        $count = $PurchaseReceiptListsModel
            ->join('think_spec_goods','think_stock_delivery_lists.spec_id=think_spec_goods.spec_id','left')
            ->join('think_goods','think_stock_delivery_lists.goods_id=think_goods.id','left')
            ->join('think_shop_lists','think_shop_lists.id=think_stock_delivery_lists.shop_id','left')
            ->group('think_stock_delivery_lists.goods_id,think_stock_delivery_lists.shop_id,think_stock_delivery_lists.spec_id')
            ->where($map)
            ->count();


        $page = input('get.page',1);
        $limits = input('get.limit',10);
        $lists = $PurchaseReceiptListsModel
            ->field('delivery_id,sum(price*num) as price,sum(num) as num,remarks,think_stock_delivery_lists.spec_id,spec_key,spec_name,spec_sku,think_stock_delivery_lists.goods_id,goods_name,goods_sn,think_stock_delivery_lists.shop_id,think_shop_lists.name as shop_name')
            ->join('think_spec_goods','think_stock_delivery_lists.spec_id=think_spec_goods.spec_id','left')
            ->join('think_goods','think_stock_delivery_lists.goods_id=think_goods.id','left')
            ->join('think_shop_lists','think_shop_lists.id=think_stock_delivery_lists.shop_id','left')
            ->group('think_stock_delivery_lists.goods_id,think_stock_delivery_lists.shop_id,think_stock_delivery_lists.spec_id')
            ->where($map)
            ->page($page,$limits)
            ->select();
        $sum = $PurchaseReceiptListsModel
            ->field('sum(price*num) as price,sum(num) as num')
            ->join('think_spec_goods','think_stock_delivery_lists.spec_id=think_spec_goods.spec_id','left')
            ->join('think_goods','think_stock_delivery_lists.goods_id=think_goods.id','left')
            ->join('think_shop_lists','think_shop_lists.id=think_stock_delivery_lists.shop_id','left')
            ->where($map)
            ->page($page,$limits)
            ->find();
        $shop_map=[];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
        }
        $ShopListsModel = new ShopListsModel();
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$count,'lists'=>$lists,'shop_lists'=>$ShopListsModel->getKeyVal($shop_map)],'msg'=>'']);

    }


}