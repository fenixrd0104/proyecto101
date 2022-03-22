<?php

namespace app\admin\controller;
use app\common\model\MemberWalletLogModel;
use think\facade\Db;

class MemberWalletLog //extends Base
{

    public function index()
    {
        $mobile = input('mobile', '');
        $account = input('account', '');
        $type = input('type', '');
        $start_time = input('startime', '');
        $end_time = input('endtime', '');
        $order = input('order', 'id desc');
        $map = [];
        if ($start_time && $start_time !== "") {
            $map[] = ['w.create_time', '>=', strtotime($start_time)];
        }
        if ($end_time && $end_time !== "") {
            $map[] = ['w.create_time', '<=', strtotime($end_time)];
        }
        if ($account && $account !== "") {
            $map[] = ['m.account', 'like', "%" . $account . "%"];
        }
        if ($type && $type !== "") {
            $map[] = ['w.type', '=', $type];
        }
        if ($mobile && $mobile !== "") {
            $map[] = ['m.mobile', '=', $mobile];
        }
        $Nowpage = input('get.page') ? input('get.page') : 1;
        $limits = input('get.limit', 10);
        $MemberWalletLogModel = new MemberWalletLogModel();
        $lists = $MemberWalletLogModel->getMemberWalletLogByWhere($Nowpage,$limits,$map, $order);
        return json(['code' => 1, 'data' => $lists, 'msg' => '']);
    }


   //ivt subscription management=3,//ivt capital preservation recovery=8
    public function lvt_rg(){
            $id=input('id');
            $Nowpage=input('page',1);
            $limit=input('limit',10);
            $map=[];

            $name=input('name','');
            $account=input('account','');
            $currency=input('currency','');
            $create_time=input('create_time','');//Y-m-d H:i:s
            $update_time=input('update_time','');//Y-m-d H:i:s

            if($account && $account!=''){
                $map[]=['m.account','=',$account];
            }
            if($name && $name!=''){
                $map[]=['m.realname','like','%'.$name.'%'];
            }
            if($currency && $currency!=''){
                $map[]=['w.currency','=',$currency];
            }
            if($create_time && $create_time!=''){
                $map[]=['w.create_time','>',strtotime($create_time)];
            }else if($update_time && $update_time!=''){
                $map[]=['w.create_time','<',strtotime($update_time)];
            }
            if($create_time && $update_time){
                $map[]=['w.create_time','between',[strtotime($create_time),strtotime($update_time)]];
            }
            $map[]=['w.type','=',$id];
            $order='w.id desc';
            $memberwalletlog=new MemberWalletLogModel();
            $data=$memberwalletlog->getMemberWalletLogByWhere($Nowpage,$limit,$map,$order);
            if($data){
                return json(['code'=>1,'data'=>$data,'msg'=>'']);
            }else{
                return json(['code'=>0,'data'=>'','msg'=>'Request failed']);
            }

    }



}