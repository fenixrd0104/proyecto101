<?php

namespace app\api\controller;
use app\common\model\Member;
use app\common\model\MemberWalletModel;
use think\Controller;
use think\facade\Db;

		/**
		* swagger: advertising
		*/
		class Ad
		{
		/**
		Get the wap homepage ad list
		*/
		public function wap_index(){
		$list = array(
		1 => get_adinfo(35,$type='list'),//banner carousel ad
		2 => get_adinfo(61, $type='single'),//announcement
		3 => get_adinfo(62, $type='article')//announcement
		);
		return json($list);
	}

	public function wap_detail($id){
    	$info = Db::name('article')->where(['id'=>$id,'cate_id'=>2])->find();
    	return json(['status'=>1,'data'=>$info,'msg'=>'']);
    }

	public function a(){
		$a = new Member();
		$uid = 1010;
		$a = $a->cz_address($uid);
		return $a;
	}

	
}