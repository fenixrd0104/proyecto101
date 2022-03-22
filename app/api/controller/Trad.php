<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/27
 * Time: 9:44
 */

namespace app\api\controller;

use app\common\model\MemberWalletLogModel;
use app\common\service\Users;
use think\facade\Db;

class Trad
{
    /*
        * Recharge interface
         * $id coin id
         * $cz_address deposit address
         * $num deposit amount
         * $trad transaction hash
         * $sign signature
         * $time timestamp
         * */
    public function topsuccess($id,$cz_address,$num,$trad,$sign,$time){
        $key = '81ddc94d86d0a1a2d047';
        $keys = md5($key.$time);
        if($keys != $sign){
            return json(['code'=>0,'data'=>false,'msg'=>'']);
        }
        if(!$trad){
            return json(['code'=>0,'data'=>false,'msg'=>'']);
        }
        $res = Db::name('recharges')->where('ti_id',$trad)->find();
        if($res){
            return json(['code'=>1,'data'=>true,'msg'=>'existed']);
        }
        $arr = Db::name('member')->where('cz_address',$cz_address)->find();
        if(!$arr){
            return json(['code'=>0,'data'=>false,'msg'=>'']);
        }
        if($id == 2){

            Db::name('member')->where('cz_address',$cz_address)->update(['money'=>$arr['money']+$num]);
            MemberWalletLogModel::log($arr['id'],$num,$arr['money'],$arr['money']+$num,1,'recharge increaseKRC',$arr['id']);
            $b_name = 'KRC';
            $czlog = $this->cz_log($arr,$cz_address,$trad,$num,$b_name);
            if($czlog == true){
                //Judging the completion of daily tasks
                $pminfo = Db::name('assignment')->where(['jl_type'=>6,'status'=>1,'type'=>1])->find();
                if($pminfo){
                    $users = New Users();
                    $users->daytask($pminfo['id'],$arr['id']);
                }
                return json(['code'=>1,'data'=>true,'msg'=>'']);
            }else{
                return json(['code'=>0,'data'=>false,'msg'=>'']);
            }
        }
        if($id == 3){
            Db::name('member')->where('cz_address',$cz_address)->update(['pool_hatch'=>$arr['pool_hatch']+$num]);
            MemberWalletLogModel::log($arr['id'],$num,$arr['pool_hatch'],$arr['pool_hatch']+$num,21,'recharge increaseUSDT',$arr['id']);
            $b_name = 'USDT';
            $czlog = $this->cz_log($arr,$cz_address,$trad,$num,$b_name);
            if($czlog == true){
                //Judging the completion of daily tasks
                $pminfo = Db::name('assignment')->where(['jl_type'=>6,'status'=>1,'type'=>1])->find();
                if($pminfo){
                    $users = New Users();
                    $users->daytask($pminfo['id'],$arr['id']);
                }
                return json(['code'=>1,'data'=>true,'msg'=>'']);
            }else{
                return json(['code'=>0,'data'=>false,'msg'=>'']);
            }
        }
        if($id == 4){
            Db::name('member')->where('cz_address',$cz_address)->update(['pool_water'=>$arr['pool_water']+$num]);
            MemberWalletLogModel::log($arr['id'],$num,$arr['pool_water'],$arr['pool_water']+$num,41,'recharge increaseSM',$arr['id']);
            $b_name = 'SM';
            $czlog = $this->cz_log($arr,$cz_address,$trad,$num,$b_name);
            if($czlog == true){

                //Judging the completion of daily tasks
                $pminfo = Db::name('assignment')->where(['jl_type'=>6,'status'=>1,'type'=>1])->find();
                if($pminfo){
                    $users = New Users();
                    $users->daytask($pminfo['id'],$arr['id']);
                }

                return json(['code'=>1,'data'=>true,'msg'=>'']);
            }else{
                return json(['code'=>0,'data'=>false,'msg'=>'']);
            }
        }
    }


    public function cz_log($arr,$cz_address,$trad,$num,$b_name){
        $res = Db::name('recharges')->insert([
            'uid'=>$arr['id'],
            'ordnum'=>'RE20'.time() . str_pad(mt_rand(100000000, 999999999), 5, '0', STR_PAD_LEFT),
            'chongzhi_url'=>$cz_address,
            'ti_id'=>$trad,
            'money'=>$num,
            'status'=>2,
            'mark'=>'online recharge',
            'type'=>2,
            'currency'=>$b_name,
            'created_at'=>date('Y-m-d H:i:s',time()),
            'updated_at'=>date('Y-m-d H:i:s',time())
        ]);

//        $data = Db::name('member_msg')->insert([
//            'receive_uid' => $arr['uid'],
//            'send_uid' => 0,
//            'title'=>'Online recharge to account'.$num.$b_name,
// 				'content'=>'Online recharge to account'.$num.$b_name,
//            'status'=>0,
//            'send_time'=>time(),
//        ]);
        if($res != ''){
            return true;
        }else{
            return false;
        }
    }

}