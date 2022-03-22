<?php

namespace app\admin\controller;
use app\admin\model\MsgConfig;
use think\Request;
use app\common\service\MsgApi;
//use app\common\model\Freight;

class Remind extends Base
{


   //Message reminder list
    public function index(){
        $MsgConfigModel = new MsgConfig();
		$info = $MsgConfigModel->get_msg_list();
		return json(['code'=>1,'data'=>["lists"=>$info['list'],'count'=>$info['count']],'msg'=>'']);

    }
	//Message reminder edit page
    public function add()
    {
        if(request()->isPost()){
            $MsgConfigModel = new MsgConfig();
            $data = input("post."); // Get all post variables filtered
            $data['auth'] = array_sum($data['auth']);
            $data['param'] = serialize($data['param']);
            if($MsgConfigModel->save($data)){
               return json(['code'=>1,'data'=>[],'msg'=>'Added successfully']);

            }
        }

    }
	//Message reminder edit processing
	public function edit()
    {
        $MsgConfigModel = new MsgConfig();
        if(request()->isPost()){
            $data = input("post."); // Get all post variables filtered

            $data['auth'] = array_sum($data['auth']);
            $data['param'] = serialize($data['param']);
			//Revise
            $id = $data['id'];
            $MsgConfigModel = new MsgConfig();
            if($info = $MsgConfigModel->where(['id'=>$id])->update($data)){
                return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'modification failed']);
            }

        }
        $id =input('get.id');
        $info = $MsgConfigModel->get_msg_info($id);
        return json(['code'=>1,'data'=>$info,'msg'=>'']);
        
    }

	//delete message
    public function del()
    {
        $id = input('get.id');
        if(( new MsgConfig())->where('id',$id)->delete()){
            return json(['code'=>1,'data'=>[],'msg'=>'deletion successful']);
        }else{
            return json(['code'=>0,'data'=>[],'msg'=>'deletion failed']);
        }
    }
	//Asynchronously modify the sending permission of a message
	function edit_auth(){
		$id = input("get.id");
		$auth = input("get.auth");
		if($id>0 && $auth>0){
			$user_auth = ( new MsgConfig())->where('id',$id)->value('auth');
			if($user_auth & $auth){
                $new_auth = $user_auth-$auth;
            }else{
                $new_auth = $user_auth+$auth;
            }
			if(( new MsgConfig())->where('id',$id)->update(['auth'=>$new_auth])){
			return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
			}
			}
			return json(['code'=>0,'data'=>[],'msg'=>'parameter error']);
	}
	

	
	public function template(){
		$info = ( new MsgConfig())->get_msg_list();
		return json(['code'=>1,'data'=>['count'=>$info['count'],'lists'=>$info['list']],'msg'=>'']);
	}
	//Template edit page for messages
	public function templateinfo(){
        if(request()->isPost()){
            $data = input('post.');
            if($data['id']>0 && $data['tpname']){
                $id = $data['id'];
                $tpname = $data['tpname'];
                unset($data['id']);
                unset($data['tpname']);
                $data = serialize($data);
                if(( new MsgConfig())->where('id',$id)->update([$tpname=>$data])){
                    return json(['code'=>1,'data'=>[],'msg'=>'Saved successfully']);
                }
            }
        }
		$id = input('param.id');
		$tpname = input('param.tpname');
		$info = (new MsgConfig())->get_msg_info($id);
		$tp_arr = array('sms'=>'SMS','email'=>'mail','mail'=>'internal message','weichat'=>'WeChat','push'=>'push' );
		$tpinfo = unserialize($info[$tpname]);
		if(!$tpinfo){
            $tpinfo='';
        }
		return json(['code'=>1,'data'=>[
            'tpname_zh'=>$tp_arr[$tpname],
            'info'=>$info,
            'tpinfo'=>$tpinfo
        ],'msg'=>'']);

	}

	public function test(){
		
		//The main method of sending a message can only be called after logging in, and returns an array
		//The parameters are: background message identifier id, recipient id, parameter array one-to-one corresponding to the predefined variables set by the background corresponding message
		//\app\common\service\Msg :: send(1,12,array('name'=>'Zhang San','verify'=>'110026','age'=>11));
		Be
		//When not logged in, send mobile phone or email information for the user to obtain the verification code
		//The parameters are: background message ID id, mobile phone/email, parameter array one-to-one corresponding to the predefined variables set by the background corresponding message
		//$res = \app\common\service\Msg :: send(4,212072,array('nam'=>'Zhang San','order_id'=>'110026'));
		//\app\common\service\Msg :: send_email(1,'4411@qq.com',array('name'=>'Zhang San','verify'=>'110026','age'=> 11));
		
		//print_r($res);
	}

}
