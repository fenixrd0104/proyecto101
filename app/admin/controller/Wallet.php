<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/3
 * Time: 11:27
 */

namespace app\admin\controller;

use think\facade\Db;

class Wallet extends Base
{
    public function index(){
        $uid = input('uid','');
        $chongzhi_url = input('chongzhi_url','');
        $currency = input('currency','');
        $type = input('type','');
        $status = input('status','');
        $map=[];
        if($uid&&$uid!==""){
            $map[] = ['uid','=',$uid];
        }
        if($type&&$type!==""){
            $map[] = ['type','=',$type];
        }
        if($status&&$status!==""){
            $map[] = ['status','=',$status];
        }
        if($chongzhi_url&&$chongzhi_url!==""){
            $map[] = ['chongzhi_url','=',$chongzhi_url];
        }
        if($currency&&$currency!==""){
            $map[] = ['currency','=',$currency];
        }
        $nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',10);
        $data['count'] = Db::name('recharges')->where($map)->count();
        $data['lists'] = Db::name('recharges')->where($map)->page($nowpage, $limits)->order('id','desc')->select();
        return json(['code'=>1,'data'=>$data,'msg'=>'search successful']);
    }
}