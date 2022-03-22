<?php

namespace app\admin\controller;
use app\admin\model\LogModel;
use think\facade\Db;
use com\IpLocation;
 
class Log extends Base
{

    public function index()
    {
        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['admin_id'] =  $key;          
        }      
		$arr=Db::name("admin")->where('shop_id',0)->column("username","id"); //Get the list of administrators
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits=input('limit',10);//Get the number of data bars to be displayed on each page
        $count = Db::name('log')->where($map)->count();//Calculate the total pages
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('log')->where($map)->page($Nowpage, $limits)->order('add_time desc')->select();
        if (is_object($lists)){
            $lists=json_decode($lists,true);
        }else{
            $lists=$lists;
        }
        $Ip = new IpLocation('UTFWry.dat'); // Instantiate class parameter indicates IP address library file
        foreach($lists as $k=>$v){
            $lists[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            $lists[$k]['ipaddr'] = $Ip->getlocation($lists[$k]['ip']);
        }
        return json(['msg'=>'','data'=>['lists'=>$lists,'count'=>$count,'search_user'=>$arr,'val'=>$key],'code'=>1]);
    }


    public function del_log()
    {
        $id = input('param.id');
        $log = new LogModel();
        $flag = $log->delLog($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
 
}