<?php

namespace app\common\model;
use app\common\service\Payment;
use think\Model;
use think\facade\Db;
class Withdraw extends Model
{
    // Enable automatic writing of timestamp fields
        protected $autoWriteTimestamp = true;
        protected $name = 'apply_withdraw';
        //withdrawal record
        public function getWithdrawByWhere($map, $nowpage, $limits, $order)
        {
            $res['list']=$this->alias('w')
                ->join('member m','w.uid =m.id','LEFT')
                ->where($map)->field('w.*,m.account,m.mobile,w.status as statuss')
                ->order($order)
                ->page($nowpage, $limits)
                ->select()->toArray();
    // ->withAttr('status', function($value, $data) {$status = [1=>'to be reviewed', 2=>'to account', 3=>'rejected', 4= >'Accounted to',5=>'Failed'];return $status[$value];})
            //calculate total pages
        $res['count'] = $this->getWithdrawByWhereCount($map);
        return $res;
    }


    //The total number of withdrawal records
    public function getWithdrawByWhereCount($map){
        return $this->alias('w')
            ->join('member m','w.uid =m.id','LEFT')
            ->where($map)
            ->count();
    }

    public function tixian($uid,$money){
        $fx_tixian=config('config.fx_tixian')||0;
        $system_money=round($money*$fx_tixian/100,2);
        $user_money=round($money-$system_money,2);

        if($money<100){
           return ['code'=>0,'msg'=>'withdrawal amount cannot be less than 100 yuan'];
        }
        if($money%100>0){
            return ['code'=>0,'msg'=>'The withdrawal amount must be a multiple of 100'];
        }

        //Withdraw no more than twice a day
        $ttime = strtotime(date('Y-m-d',time()));
        $map['create_time'] = array('gt',$ttime);
        $map['uid'] = $uid;
        $map['status'] = array('lt',2);

        //Check whether the current user's money is enough to withdraw
        $info=Db::name('member')->where(['id'=>$uid])->find();
        if(!$info['alipay_name'] || !$info['alipay_account']){
            return ['code'=>0,'msg'=>'Please bind the withdrawal payment account correctly first'];
        }
        $payname = "Alipay";
        $name = $info['alipay_name'];
        $hao = $info['alipay_account'];
        $yinhang = "";
        $zmoney =$info['integral'];
        if($zmoney < $money){
            return ['code'=>0,'msg'=>'Insufficient withdrawal amount'];
        }

        //Reduce money to user
        IntegralLog::setDec($uid,$money);
        // increase or decrease records
         IntegralLog::operate($uid,-$money,25,1,$payname.'Withdrawal');

        $status=0;
        $payment_no='';
        $id = $this->insert([
            'uid'=>$uid,
            'money'=>$money,
            'status'=>$status,
            'username'=>$info['account'],
            'kh_hang'=>$yinhang,
            'zfb_name'=>$name,
            'zfb_hao'=>$hao,
            'payment_no'=>$payment_no,
            'feilv'=>$fx_tixian,
            'system_money'=>$system_money,
            'user_money'=>$user_money,
            'update_time'=>time(),
            'create_time'=>time()
        ],false,true);

        if($id){
            return ['status'=>1,'msg'=>'Successful application'];
        }
    }


    public function dakuan($id){
        $info = $this->find($id);
        if($info['status']==0){
            $info->status=1;
            if($info->type ==1){
                $res = $this->pay($info,$info['user_money']);
                if($res['status'] == 1){
                    $info->status=1;
                    $info->payment_no=$res['payment_no'];
                    if($info->save()){
                        return ['code'=>1,'msg'=>'Successful payment'];
                    }else{
                        return ['code'=>0,'msg'=>'Failed to save payment'];
                    }
                }else{
                    return ['code'=>0,'msg'=>$res['msg']];
                }


            }else{
                if($info->save()){
                    return ['code'=>1,'msg'=>'Successful payment'];
                }else{
                    return ['code'=>0,'msg'=>'Failed to save payment'];
                }
            }
            /* */
            /* */
        }else{
            return ['code'=>0,'msg'=>'Cannot make payment in this state'];
        }
    }

    public function jjdakuan($id){
        $info = $this->find($id);
        if($info['status']==0){
            $info->status=2;
            if($info->save()){
                //  $Friends = new Friends();
                //member refund
                IntegralLog::setInc($info['uid'],$info['money']);
                //Record
                IntegralLog::operate($info['uid'],$info['money'],26,1,'Amount of withdrawal failure is returned');
                return ['code'=>1,'msg'=>'rejected success'];
            }else{
                return ['code'=>0,'msg'=>'rejection failed'];
            }
        }else{
            return ['code'=>0,'msg'=>'current state cannot be operated'];
        }
    }

    private function pay($user,$money,$desc='user withdrawal'){
        // $user =  Db::name('oauth_user')->where(['from'=>'weixin','uid'=>$uid])->find();


        $Payment = new Payment();
        $ret= $Payment->ali_transfer([
            'trans_no' => time(),
            'payee_type' => 'ALIPAY_LOGONID',
            'payee_account' => $user['zfb_hao'],// ALIPAY_USERID: 2088102169940354      ALIPAY_LOGONID：aaqlmq0729@sandbox.com
            'amount' => $money,
            'remark' => $desc,
            'payer_show_name' =>$user['zfb_name'],
        ]);
        /* $ret = $Payment->wx_transfer(
             [
                 'trans_no' => time(),
                 'openid' => $user['openid'],
                 'check_name' => 'NO_CHECK',// NO_CHECK: Do not verify real name FORCE_CHECK: Strongly verify real name OPTION_CHECK: Verify real name only for users who have been authenticated by real name
                 'payer_real_name' => $user['name'],
                 'amount' => $money,
                 'desc' => $desc,
                 'spbill_create_ip' => get_client_ip(1),
             ]
         );*/
        if($ret['code']==0){
            return ['code'=>0,'msg'=>$ret['msg']];
        }else{
            return ['code'=>1,'payment_no'=>$ret['data']];
        }



    }

    /*private function pay($uid,$money,$desc='User withdrawal'){
        $user =  Db::name('oauth_user')->where(['from'=>'weixin','uid'=>$uid])->find();
        if(!$user){
            return ['status'=>0,'msg'=>'No such WeChat user'];
        }
        $Payment = new Payment();
        $ret = $Payment->wx_transfer(
            [
                'trans_no' => time(),
                'openid' => $user['openid'],
                'check_name' => 'NO_CHECK',// NO_CHECK: Do not verify real name FORCE_CHECK: Strongly verify real name OPTION_CHECK: Verify real name only for users who have been authenticated by real name
                'payer_real_name' => $user['name'],
                'amount' => $money,
                'desc' => $desc,
                'spbill_create_ip' => get_client_ip(1),
            ]
        );
        if($ret['code']==0){
            return ['status'=>0,'msg'=>$ret['msg']];
        }else{
            return ['status'=>1,'payment_no'=>$ret['data']];
        }



    }*/
}

