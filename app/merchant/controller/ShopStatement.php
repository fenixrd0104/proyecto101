<?php
/**
 * 收银台
 */

namespace app\merchant\controller;
use app\merchant\model\GoodsStock;
use app\merchant\model\MemberModel;
use app\merchant\model\ShopBIllModel;
use app\merchant\model\ShopListsModel;
use app\merchant\model\ShopOrder;
use app\merchant\model\ShopOrderListsModel;
use app\merchant\model\ShopOrderModel;
use app\merchant\model\ShopPayConfigModel;
use app\merchant\model\ShopStatementModel;
use app\merchant\model\SpecGoodsModel;
use app\merchant\model\StockDeliveryListsModel;
use app\merchant\model\StockDeliveryModel;
use app\merchant\model\StockReceiptListsModel;
use app\merchant\model\StockReceiptLogModel;
use app\merchant\model\StockReceiptModel;
use app\merchant\model\UserModel;
use app\common\model\IntegralLog;
use app\common\model\MoneyLog;
use think\Exception;
use think\facade\Db;

class ShopStatement extends Base
{

    //Home
    public function index(){
        $ShopOrderModel = new ShopStatementModel();
        $map=[];
        if($this->shopId){
            $map[]=['shop_id','=',$this->shopId];
        }
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        $payStatus = input('get.status','');
        if($keyWords != ''){
              $map[]=['think_shop_statement.total_money|think_shop_statement.to_account|think_shop_statement.from_account|from_uid|to_uid|think_shop_lists.name', 'like', "%".$keyWords."%"];
        }
        if($startTime){
            $map[]=['think_shop_statement.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_shop_statement.create_time','<',strtotime($endTime)];
        }

        if($payStatus != ''){
            $map[]=['think_shop_statement.status','=',$payStatus];
        }

        $limits = input('get.limit',10);
        $res = $ShopOrderModel->getPaginate($map,$limits);
        $sum = $ShopOrderModel->getSumArr($map);
        $lists = $res->toArray();
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data'],'status'=>new \ArrayObject($ShopOrderModel->StatusArr)],'msg'=>'']);
    }
    //Choose to sell
    public function getCashierLists(){
        $UserModel = new UserModel();
        $keyWords = input('get.keyWords','');
        $map = [];
        $map[]=['shop_id','>',0];
        if($this->shopId){
            $map[]=['shop_id','=',$this->shopId];
        }
        if($keyWords&&$keyWords!=="")
        {
            $map[] = ['username|real_name','like',"%" . $keyWords . "%"];
        }
        $map[] = ['think_admin.id','<>',$this->userId];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',10);
        $count =  $UserModel->where($map)->count();
        $lists =  $UserModel->field('think_admin.id,username,real_name,shop_id,think_shop_lists.name shop_name')->join('think_shop_lists','think_shop_lists.id=think_admin.shop_id','left')->where($map)->page($Nowpage,$limits)->select();
        return json(['code'=>1,'data'=>['lists'=>$lists,'count'=>$count],'msg'=>'']);
    }
    //View Submit
    public function generate(){
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $start_time=input('start_time',0,'urldecode');
        $end_time=input('end_time',0,'urldecode');
        $to_uid=input('post.to_uid',0);
        $from_remarks=input('post.from_remarks','');

        $ShopBIllModel =  new ShopBIllModel();
        $lists = $ShopBIllModel->getStatement($this->userId,$shop_id,strtotime($start_time),strtotime($end_time));
        if(request()->isPost()){
            if(!$to_uid){
                return json(['code'=>0,'data'=>[],'msg'=>'No handover object selected']);
            }
            $ShopStatementModel = new ShopStatementModel();
            $lists['shop_id']=$shop_id;
            $lists['from_uid']=$this->userId;
            $lists['from_account']=$this->userName;
            $lists['from_account']=$this->userName;
            $lists['to_uid']=$to_uid;
            $lists['to_account']=Db::name('admin')->where(['id'=>$to_uid])->value('username');
            $lists['status']=1;
            $lists['from_remarks']=$from_remarks;
            $lists['start_time']=strtotime($start_time);
            $lists['end_time']=strtotime($end_time);
            $lis = $lists['lists']->toArray();
            unset($lists['lists']);
            $lists['lists']=serialize($lis);
            $ShopStatementModel->save($lists);
            return json(['code'=>1,'data'=>$ShopStatementModel,'msg'=>'']);


        }
        return json(['code'=>1,'data'=>$lists,'msg'=>'']);
    }
    // view cancel
    public function cancel(){
        $id = input('get.id',0);
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $map[]=['shop_id','=',$shop_id];
        $map[]=['id','=',$id];
        $map[]=['to_uid|from_uid','=',$this->userId];
        $ShopOrderModel = new ShopStatementModel();
        $info = $ShopOrderModel->where($map)->find();

        if(!$info || $info->getData('status') != 1){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be cancelled']);
        }
        $info->status = 3;
        if($info->save()){
            return json(['code'=>1,'data'=>[],'msg'=>'Cancel success']);
        }
        return json(['code'=>0,'data'=>[],'msg'=>'cancellation failed']);
    }
    // view audit
    public function audit(){
        $id = input('id',0);
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $map[]=['shop_id','=',$shop_id];
        $map[]=['id','=',$id];
        if(request()->isPost()){
            $map[]=['to_uid','=',$this->userId];
            $ShopOrderModel = new ShopStatementModel();
            $info =  $ShopOrderModel->where($map)->find();
            if(!$info){
                return json(['code'=>0,'data'=>[],'msg'=>'No statement for you!']);
            }
            if($info->getData('status') != 1){
                return json(['code'=>0,'data'=>[],'msg'=>'Current status cannot be reconciled']);
            }
            $info->status = 2;
            if($info->save()){
                return json(['code'=>1,'data'=>[],'msg'=>'Reconciliation successful']);
            }
            return json(['code'=>0,'data'=>[],'msg'=>'Reconciliation failed']);
        }
        $ShopOrderModel = new ShopStatementModel();
        $info = $ShopOrderModel->where($map)->find();
        if(!$info){
            return json(['code'=>0,'data'=>[],'msg'=>'No statement!']);
        }
        $info['lists']=unserialize($info['lists']);
        $btu=[];
        if($info->getData('status') == 1){
            if($info->to_uid == $this->userId ||$info->from_uid == $this->userId ){
                $btu[]='quxiao';
            }
            if($info->to_uid == $this->userId ){
               $btu[]='queren';
            }
        }

        $info['btu']=$btu;
        return json(['code'=>1,'data'=>$info,'msg'=>'']);
    }




}