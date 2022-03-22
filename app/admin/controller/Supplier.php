<?php

namespace app\admin\controller;
use app\admin\model\GoodsSupplierModel;
use app\admin\model\ShopListsModel;
use app\admin\model\SupplierMoneyLog;
use think\exception\ValidateException;
use think\facade\Db;

class Supplier extends Base
{

    private $supplier_status=[
			0=>'Not reviewed',
            1=>'normal',
            2 => 'disable'
          ];

    public function index(){
        $key = input('keywords');
        $map = [];
        if($key&&$key!=="")
        {
            $map[] = ['supplier_name|supplier_contacts|supplier_phone|supplier_addr','like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
		$limits = config('app.list_rows');// Get the total number of rows
        $GoodsSupplierModel = new GoodsSupplierModel();
        $count = $GoodsSupplierModel->getAll($map);//Calculate the total pages
        $lists = $GoodsSupplierModel->getByWhere($map, $Nowpage, $limits);
        $sum = $GoodsSupplierModel->where($map)->sum('supplier_balance');
        foreach ($lists as $v){
            $v->forShopName = $v->forShop()->value('name');
            $v->supplier_status=$this->supplier_status[$v->supplier_status];
        }
        return json(['code'=>1,'data'=>['sum'=>$sum,'lists'=>$lists,'count'=>$count],'msg'=>'']);
    }
    public function add()
    {
        if(request()->isPost()){
            $param = input('post.');
            try {
                $this->validate($param,'SupplierValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code'=>0,'data'=>[],'msg'=>$e->getError()]);
            }
            $model = new GoodsSupplierModel();
            return json($model->insertOne($param));
        }
    }
    public function edit()
    {
        $model = new GoodsSupplierModel();
        if(request()->isPost()){
            $param = input('post.');
            try {
                $this->validate($param,'SupplierValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code'=>0,'data'=>[],'msg'=>$e->getError()]);
            }
            $flag = $model->editOne($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.supplier_id');
       return json(['code'=>1,'data'=>['info'=>$model->find($id),'supplier_status'=>new \ArrayObject($this->supplier_status)],'msg'=>'']);
    }
    public function del()
    {
        $supplier_id = input('param.supplier_id');
        $role = new GoodsSupplierModel();
        $flag = $role->delOne($supplier_id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    public function get($id,GoodsSupplierModel $model){
        $info = $model->find($id);
        $info->supplier_status=$this->supplier_status[$info->supplier_status];
        return json(['code'=>1,'data'=>$info,'msg'=>'']);
    }
    public function state()
    {
        $id = input('param.supplier_id');
        $status = Db::name('goods_supplier')->where('supplier_id',$id)->value('supplier_status');//Judging the current state
        if($status==1)
        {
            $flag = Db::name('goods_supplier')->where('supplier_id',$id)->update(['supplier_status'=>0]);
			return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        }
        else
        {
            $flag = Db::name('goods_supplier')->where('supplier_id',$id)->update(['supplier_status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => 'enabled']);
        }
    
    }

    //Supplier bill
    public function settlementLists(){
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        $shop_id = input('get.shopId',0,'trim');
        $type = input('get.type','','trim');
        $settlement = input('get.settlement','','trim');
        $SupplierMoneyLog = new SupplierMoneyLog();

        if($this->shopId){
            $map[]=['think_goods_supplier_money_log.shop_id','=',$this->shopId];
            $shop_id=$this->shopId;
        }else  if($shop_id){
            $map[]=['think_goods_supplier_money_log.shop_id','=',$shop_id];
            $shop_id=$shop_id;
        }



        if($startTime){
            $map[]=['think_goods_supplier_money_log.create_time','>',strtotime($startTime)];
        }else{
            $map[]=['think_goods_supplier_money_log.create_time','>',time()-3600*24*31];
        }
        if($endTime){
            $map[]=['think_goods_supplier_money_log.create_time','<',strtotime($endTime)];
        }else{
            $map[]=['think_goods_supplier_money_log.create_time','<',time()];
        }
        if($keyWords){
            $map[]=['think_goods_supplier.supplier_name|think_shop_lists.name','like',"%'.$keyWords.'%"];
        }
        if($type){
            $map[]=['think_goods_supplier_money_log.type','=',$type];
        }
        if($keyWords){
            $map[]=['think_goods_supplier_money_log.type','=',$type];
        }

        $page=input('get.page',1);
        $limit=input('get.limit',10);

        $count =$SupplierMoneyLog->getCount($map);
        $data = $SupplierMoneyLog->getLists($map,$page,$limit);
        $shop_map=[];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
        }
        $ShopListsModel =   new ShopListsModel();

        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$data,'shop_lists'=>$ShopListsModel->getKeyVal($shop_map),'type'=>new \ArrayObject($SupplierMoneyLog->typeStatus),'settlement'=>new \ArrayObject($SupplierMoneyLog->settlementStatus)],'msg'=>'']);


    }
    //Vendor Billing Operations
    public function SettlementAct(){
        $map = [];
        $id = input('post.id');
        $remark = input('post.remark','');
        $map[]=['id','=',$id];
        if($this->shopId){
            $map[]=['shop_id','=',$this->shopId];
        }
        $SupplierMoneyLog = new SupplierMoneyLog();
        $info = $SupplierMoneyLog->where($map)->find();
        if(!$info){
			return json(['code'=>0,'data'=>[],'msg'=>'The bill was not found']);
        }
        if($info->getData('settlement') != 0){
            return json(['code'=>0,'data'=>[],'msg'=>'The bill cannot be settled']);
        }
        Db::startTrans();
		// start billing
        $info->remark=$remark;
        $info->settlement=1;
        $info->uid=$this->userId;

        if($info->save()){
            //Add or subtract money to the user
            if($info->getData('type') == 1){
                $money=-$info->money;
            }else{
                $money=abs($info->money);
            }
            Db::name('goods_supplier')->where(['supplier_id'=>$info->supplier_id])->update(['supplier_balance'=>Db::raw("supplier_balance+$money")]);
            Db::commit();
            return json(['code'=>1,'data'=>[],'msg'=>'settlement completed']);
        }else{
            Db::rollback();
            return json(['code'=>0,'data'=>[],'msg'=>'Save failed']);
        }



    }

}