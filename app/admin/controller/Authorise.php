<?php

namespace app\admin\controller;
use think\facade\Db;

class Authorise extends Base
{
    //Third-party login configuration
   public function index(){
   	    $list=Db::name('auth_config')->where(array('model'=>1))->select();
   	    return json(['code'=>1,'data'=>$list,'msg'=>'']);
   }
   public function config(){
       if(request()->isPost()){
           $id=input("id","");
           $post=input('post.');
           foreach ($post as $k=>$v){
               if(!$v){
				return json(['code'=>0,'data'=>[],'msg'=>$k.'Cannot be empty']);
               }
           }
           if(Db::name("auth_config")->where(array("id"=>$id))->update(array("config"=>serialize($post)))){
              return json(['code'=>1,'data'=>[],'msg'=>'update successful']);
           } else{
               return json(['code'=>0,'data'=>[],'msg'=>'update failed']);
           }
       }
	   	$id=input('id');
	   	$res=Db::name("auth_config")->find($id);
	   	$res['config']=unserialize($res['config']);
	   	$res['param']=unserialize($res['param']);
	   	return json(['code'=>1,'data'=>$res,'msg'=>'']);
   }
   /**
    * Update configuration
    */
   public function status_config(){
	   	$id=input('id');
	   	$status=Db::name("auth_config")->where(array("id"=>$id))->value('status');
	   	if($status==1) {
            $status=0;
			$suc='Disable success';
			}else {
			$status=1;
            $suc='Enable success';
	   	};
	   	if(Db::name("auth_config")->where(array("id"=>$id))->update(array('status'=>$status))){
	   	    return json(['code'=>1,'data'=>[],'msg'=>$suc]);
	   	}
   }

}