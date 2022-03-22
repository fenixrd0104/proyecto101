<?php

namespace app\admin\controller;
use app\common\model\MemberWalletLogModel;
use app\common\model\MemberWalletModel;
use app\common\model\Withdraw;
use app\common\model\Member;
use app\common\service\Users;
use think\facade\Db;

class Withdrawal extends Base
{

    public function index() {
        $mobile = input('mobile','');
        $id = input('id','');
        $status = input('status','');
        $currency = input('currency','');
        $qianbao_url = input('qianbao_url','');
        $txnum = input('txnum','');
        $start_time = input('startime','');
        $end_time = input('endtime','');
        $order       = input('order', 'created_at desc');
        $us_nums    = config('config.us_nums');
        $withdraw_nums=Db::name('apply_withdraw')->where('status',4)->sum('handling_fee');
        $surplus_nums = $us_nums-$withdraw_nums;
        $map=[];
        if($start_time&&$start_time!==""){
            $map[] = ['w.created_at','>=',strtotime($start_time)];
        }
        if($end_time&&$end_time!==""){
            $map[] = ['w.created_at','<=',strtotime($end_time)];
        }
        if($txnum&&$txnum!==""){
            $map[] = ['w.txnum','=',$txnum];
        }
        if($currency&&$currency!==""){
            $map[] = ['w.currency','=',$currency];
        }
        if($mobile&&$mobile!==""){
            $map[] = ['m.mobile','=',$mobile];
        }
        if($qianbao_url&&$qianbao_url!==""){
            $map[] = ['qianbao_url','like',$qianbao_url.'%'];
        }
        if($id&&$id!==""){
            $map[] = ['w.id','=',$id];
        }
        if($status&&$status!=0){
            $map[] = ['w.status','=',$status];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $Withdraw = new Withdraw();
        $lists = $Withdraw->getWithdrawByWhere($map, $Nowpage, $limits,$order);
		$lists['status']=array(1=>'to be reviewed', 3=>'rejected', 4=>'accepted');
        $lists['pid']=array(1=>'KRC',2=>'USDT',3=>'Gift coins');

        return json(['code'=>1,'data'=>$lists,'us_nums'=>$us_nums,'withdraw_nums'=>$withdraw_nums,'surplus_nums'=>$surplus_nums,'msg'=>'']);
    }
	//Withdrawal approved
    public function remit(){
        $id=input('id');
        $ti_id=input('ti_id');
        $Withdraw=new Withdraw();
        $res=$Withdraw->where(['id'=>$id])->field('id,money,uid,pid,status,actual,again,qianbao_url')->find();
        if($res['status']>2){
		return json(['code'=>0,'data'=>[],'msg'=>'status exception']);
        }
        $lock_money=Db::name('member')->where(array('id'=>$res['uid']))->value($res['pid']);
        // start transaction
        Db::startTrans();
        try {
                //Agree to the account
                $Withdraw->where(['id'=>$id])->save(['status'=>2,'updated_at'=>time(),'ti_id'=>$ti_id]);
                //User wallet minus freeze
                //if(Member::kouchu_dongjie($res['uid'],$res['money'],$res['pid'])){
                    //TODO::Pay to account
                    $tx = $this->tx($res,$lock_money);
                    if($tx == false){
                        Db::rollback();
                        return json(['code'=>0,'data'=>[],'msg'=>'Withdrawal failed']);
                    }

                   /* //write log
                    $data=array(
                        'receive_uid'=>$res['uid'],
                        'title'=>'Withdrawal and payment approved',
                        'content'=>'withdrawal and payment approved',
                        'status'=>0,
                        'send_time'=>time(),
                    );
			//insert a message
                    Db::name('member_msg')->save($data);*/
               // }
                // commit the transaction
                Db::commit();
                return json(['code'=>1,'data'=>[],'msg'=>'withdrawal successful']);
        } catch (\Exception $e) {
            // rollback transaction
            Db::rollback();
            return json(['code'=>0,'data'=>[],'msg'=>$e->getMessage()]);
        }
    }

    public function tx($res,$lock_money){
        $arr = new Users();
        if($res['pid'] == 'lock_money'){
            $url = 'sendtoaddress';
            $coin = 'krc';
            $params['method'] = 'sendtoaddress';
            $params['params'][] = $res['qianbao_url'];
            $params['params'][] = $res['actual'];
            $params['params'][] = 1;
            $params['id'] = 2;
            $arr = $arr->signedRequest($coin,$url,$params);
            if($arr['result'] != ''){
                Db::name('apply_withdraw')->where('id',$res['id'])->update(['status'=>4,'ti_id'=>$arr['result']]);
               // MemberWalletLogModel::log($res['uid'],$res['money'],$lock_money,$lock_money-$res['money'],6,'KRCWithdraw through',$res['uid']);
                return true;
            }
        }

        if($res['pid'] == 'lock_usdt'){
            $url = 'sendtoaddress';
            $coin = 'usdt';
            $params['method'] = 'sendtoaddress';
            $params['params'][] = $res['qianbao_url'];
            $params['params'][] = $res['actual'];
            $params['params'][] = 1;
            $params['id'] = 3;
            $arr = $arr->signedRequest($coin,$url,$params);
            if($arr['result'] != ''){
                Db::name('apply_withdraw')->where('id',$res['id'])->update(['status'=>4,'ti_id'=>$arr['result']]);
               // MemberWalletLogModel::log($res['uid'],$res['money'],$lock_money,$lock_money-$res['money'],26,'USDT Withdraw through',$res['uid']);
                return true;
            }
        }

        if($res['pid'] == 'lock_xxxx'){
            $url = 'sendtoaddress';
            $coin = 'sm';
            $params['method'] = 'sendtoaddress';
            $params['params'][] = $res['qianbao_url'];
            $params['params'][] = $res['actual'];
            $params['params'][] = 1;
            $params['id'] = 4;
            $arr = $arr->signedRequest($coin,$url,$params);
            if($arr['result'] != ''){
                Db::name('apply_withdraw')->where('id',$res['id'])->update(['status'=>4,'ti_id'=>$arr['result']]);
                //MemberWalletLogModel::log($res['uid'],$res['money'],$lock_money,$lock_money-$res['money'],44,'SM Withdraw through',$res['uid']);
                return true;
            }
        }

        return false;
    }
	//Withdrawal review rejected
    public function reject(){
        $id=input('id');
        $Withdraw=new Withdraw();
        $res=$Withdraw->where(['id'=>$id])->field('money,uid,pid,status,actual')->find();
        if($res['status']!=1){
            return json(['code'=>0,'data'=>[],'msg'=>'application status abnormal']);
        }
	// start transaction
        Db::startTrans();
        try {
            //Audit not passed
            $Withdraw->where(['id'=>$id])->save(['status'=>3]);
            //User wallet release and freeze
            $tx = $this->jtx($res);
            if($tx == true){
              /* $data=array(
                    'receive_uid'=>$res['uid'],
                    'title'=>'Failed to review the withdrawal and payment',
                    'content'=>'Failed to review the withdrawal and payment',
                    'status'=>0,
                    'send_time'=>time(),
                );
                //insert a message
                Db::name('member_msg')->save($data);*/
            }else{
                return json(['code'=>1,'data'=>[],'msg'=>'Withdrawal review and rejection failed' ]);
            }

            // commit the transaction
            Db::commit();
            return json(['code'=>1,'data'=>[],'msg'=>'withdrawal review rejected successfully' ]);
        } catch (\Exception $e) {
            // rollback transaction
            Db::rollback();
            return json(['code'=>0,'data'=>[],'msg'=>$e->getMessage()]);
        }
    }

    public function jtx($res){
        if($res['pid'] == 'lock_money'){
            $ymoney = Db::name('member')->where('id',$res['uid'])->value('money');
            $money = Db::name('member')->where('id',$res['uid'])->value('lock_money');
            $nows = $money - $res['money'];
            if( $nows <0){
                return false;
            }
            $nmoney = $res['money']+$ymoney;
            Db::name('member')->where('id',$res['uid'])->update(['money'=>$nmoney,'lock_money'=>$nows]);
            MemberWalletLogModel::log($res['uid'],$res['money'],$ymoney,$nmoney,5,'KRC Withdrawal refused',$res['uid']);
            return true;
        }

        if($res['pid'] == 'lock_usdt'){
            $ymoney = Db::name('member')->where('id',$res['uid'])->value('pool_hatch');
            $money = Db::name('member')->where('id',$res['uid'])->value('lock_usdt');
            $nows = $money - $res['money'];
            if( $nows <0){
                return false;
            }
            $nmoney = $res['money']+$ymoney;
            Db::name('member')->where('id',$res['uid'])->update(['pool_hatch'=>$nmoney,'lock_usdt'=>$nows]);
            MemberWalletLogModel::log($res['uid'],$res['money'],$ymoney,$nmoney,25,'USDT Withdrawal refused',$res['uid']);
            return true;

        }

        if($res['pid'] == 'lock_xxxx'){
            $ymoney = Db::name('member')->where('id',$res['uid'])->value('pool_water');
            $money = Db::name('member')->where('id',$res['uid'])->value('lock_xxxx');
            $nows = $money - $res['money'];
            if( $nows <0){
                return false;
            }
            $nmoney = $res['money']+$ymoney;
            Db::name('member')->where('id',$res['uid'])->update(['pool_water'=>$nmoney,'lock_xxxx'=>$nows]);
            MemberWalletLogModel::log($res['uid'],$res['money'],$ymoney,$nmoney,43,'SM Withdrawal refused',$res['uid']);
            return true;

        }

        return false;
    }

}