<?php

namespace app\merchant\controller;
use app\merchant\model\MemberModel;
use app\merchant\model\MemberGroupModel;
use app\common\model\IntegralLog;
use app\common\model\TianyuanLog;
use app\common\service\Settlement;
use think\exception\ValidateException;
use think\facade\Db;
use think\Request;
use app\common\service\Users;


class Member extends Base
{
    //*********************************************member group*********************************************//
    /**
     * [group member group]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function group(){
        $key = input('keywords');
        $map = [];
        if($key&&$key!==""){
            $map[] = ['group_name','like',"%" . $key . "%"];
        }
        $group = new MemberGroupModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',10);
        $count = $group->getAllCount($map); //Get the total count
        $lists = $group->getAll($map, $Nowpage, $limits);
        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$lists],'msg'=>'']);

    }

    /**
     * [add_group add member group]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function add_group()
    {
        if(request()->isPost()){
            $param = input('post.');
            $group = new MemberGroupModel();
            try {
                $this->validate($param,'MemberGroupValidate');
            } catch (ValidateException $e) {
              // validation failed output error message
                return json(['code'=>0,'data'=>[],'msg'=>$e->getError()]);
            }
            $flag = $group->insertGroup($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

    }


    /**
     * [edit_group edit member group]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function edit_group()
    {
        $group = new MemberGroupModel();
        if(request()->isPost()){           
            $param = input('post.');
            try {
                $this->validate($param,'MemberGroupValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code'=>0,'data'=>[],'msg'=>$e->getError()]);
            }
            $flag = $group->editGroup($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        return json(['code'=>1,'data'=>$group->getOne($id),'msg'=>'']);

    }


    /**
     * [del_group delete member group]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function del_group()
    {
        $id = input('param.id');
        $group = new MemberGroupModel();
        $flag = $group->delGroup($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * [group_status member group status]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function group_status()
    {
        $id=input('param.id');
        $status = Db::name('member_group')->where(array('id'=>$id))->value('status');//Judge the current status
        if($status==1)
        {
            $flag = Db::name('member_group')->where(array('id'=>$id))->update(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        }
        else
        {

            $flag = Db::name('member_group')->where(array('id'=>$id))->update(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => 'enabled']);
        }   
    } 


    //*********************************************member list* ********************************************//
    /**
     * member list
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function index(){

        $key = input('keywords');
        $map[] =['closed','=',0];//0 is not deleted, 1 is deleted
        if($key&&$key!=="")
        {
            $map[] = ['account|nickname|mobile','like',"%" . $key . "%"];
        }
        $member = new MemberModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',10);
        $count = $member->getAllCount($map);//Calculate the total pages
        $lists = $member->getMemberByWhere($map, $Nowpage, $limits);
        return json(['code'=>1,'data'=>['lists'=>$lists,'count'=>$count],'msg'=>'']);
    }


    /**
     * Add member
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function add_member()
    {
        if(request()->isPost()){
            $param = input('post.');
            $param['password'] = md5(md5($param['password']) .config('app.auth_key'));
            $member = new MemberModel();
            $group_id=0;
            //The newly added is not equal to the default group
            if($param['group_id'] != config('config.default_user_group')){
                $group_id=$param['group_id'];
                $param['group_id']=config('config.default_user_group');
            }
            try {
                $this->validate($param,'MemberValidate');
            } catch (ValidateException $e) {
                // validation failed output error message
                return json(['code'=>0,'data'=>[],'msg'=>$e->getError()]);
            }
            $flag = $member->insertMember($param);
            if($param['referid']){
                event('MemberReferidReg',['uid'=>$flag['data'],'referid'=>$param['referid']]);
            }
            if($group_id){
                event('MemberGroupChange',['uid'=>$flag['data'],'group_id'=>$group_id]);
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $group = new MemberGroupModel();
        return json(['code'=>1,'data'=>['group'=>$group->getGroup()],'msg'=>'']);

    }


    /**
     * Edit member
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function edit_member()
    {
        $member = new MemberModel();
        if(request()->isPost()){
            $param = input('post.');
            try {
                $this->validate($param,'MemberValidate');
            } catch (ValidateException $e) {
                // validation failed output error message
                return json(['code'=>0,'data'=>[],'msg'=>$e->getError()]);
            }
            if(empty($param['password'])){
                unset($param['password']);
            }else{
                $param['password'] = md5(md5($param['password']) . config('app.auth_key'));
            }
            unset($param['file']);
            $flag = $member->editMember($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $group = new MemberGroupModel();

        return json(['code'=>1,'data'=>['info' => $member->getOneMember($id),
            'group' => $group->getGroup()],'msg'=>'']);

    }


    /**
    * delete member
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function del_member()
    {
        $id = input('param.id');
        $member = new MemberModel();
        $flag = $member->delMember($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * Member status
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function member_status()
    {
        $id = input('param.id');
        $status = Db::name('member')->where('id',$id)->value('status');//Judge the current status
        if($status==1)
        {
            $flag = Db::name('member')->where('id',$id)->update(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        }
        else
        {
            $flag = Db::name('member')->where('id',$id)->update(['status'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'enabled']);
        }
    
    }


    //Third Party Authorized Member
	public function authorise(){
        $limits = input('get.limit',15);
		$list = Db::name('oauth_user')->alias('oauth_user')->
		field('think_member.status as member_status,nickname,last_login_ip,create_time,update_time,login_num,head_img,closed,oauth_user.*')->
		join('think_member','oauth_user.uid=think_member.id')->
		where('think_member.status=1 and closed=0')->order('login_num','desc')->paginate($limits);
		$lists = ($list->toArray());
		return json(['code'=>1,'data'=>['count'=>$lists['total'],'lists'=>$lists['data']],'msg'=>'']);
	}


	
	
	/**
	* Delete third-party users
	* @param
	* @return
	*/
	    public function autorise_del($id){
	    if(db('member')->where('id',$id)->update(array('closed'=>1))){
	    $this->success('Delete successful');
	    }else{
	    $this->success('Deletion failed');
	    }
	    }
	//Member level list
	public function grade(){
	$kw=input('kw');
	$list=db('member_grade')->where('name','like',"%{$kw}%")->select();
	$this->assign('list', $list);
	$this->assign('kw',$kw);
	return $this->fetch();
	}
	//level edit
	public function grade_eidt($id){
	  $row=db('member_grade')->find($id);
	  $this->assign('row',$row);
	  return $this->fetch();
	}
	/***
	 * Level update
	 */
	public function ajax_grade_update(){
		 $model=model('MemberGrade');
		 $data=input('post.');
		 $info='';
		 if(isset($data['id'])){
		 	$info=$model->updataGrade($data);
		 }else{
		 	$info=$model->addGrade($data);
		 }
		 return json($info);
	}
	// add level
	public function grade_add(){
	return $this->fetch();
	}
	//level delete
	public function grade_del(){
	$id=input('id');
	if(db('member_grade')->delete($id)){
	return json(['code'=>1,'msg'=>'deletion successful']);
	}else{
	return json(['code'=>0,'msg'=>'deletion failed']);
	}
	}
	
	//Generate business discount card
    public function create_card($num=100){
	    $data=[];
	    for($i =0;$i<$num;$i++){
	       $mt =  mt_rand(100000,999999);
           $mi = microtime();
            $data[]=[
                'card_id'=>substr(md5($mi.$mt.uniqid()),8,16),
                'create_time'=>time(),
            ];
        }
	    if(Db::name('member_card')->insertAll($data)){
	        return json(['code'=>1,'data'=>[],'msg'=>'Created successfully']);
        };
    }

    public function create_card_lists(){
        $key = input('keywords');
        $map=[];
        if($key&&$key!=="")
        {
            $map[] = ['card_id|uid|account','like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count = Db::name('member_card')->where($map)->count();//Calculate total pages
        $lists = Db::name('member_card')->where($map)->page($Nowpage, $limits)->select();
        return json(['code'=>1,'data'=>['lists'=>$lists,'count'=>$count],'msg'=>'']);
    }
    public function create_card_bangka($uid,$card_id){
        //First determine whether it has been bound
       $card = Db::name('member_card')->where(['card_id'=>$card_id])->find();
       if($card['uid']){
           return json(['code'=>0,'data'=>[],'msg'=>'The card has been bound']);
       }
        // no more binding
       $user = Db::name('member')->where(['id'=>$uid])->find();
       if($user['is_bangka']){
           return json(['code'=>0,'data'=>[],'msg'=>'members who are bound to the card cannot bind the card again']);
       }
        if(Db::name('member_card')->where(['card_id'=>$card_id])->update([
           'uid' => $uid,
           'account' =>$user['account'],
           'status' => 1,
           'update_time' =>time(),
        ])){
            (new Settlement(config('config')))->bangka($uid);
           return json(['code'=>1,'data'=>[],'msg'=>'binding successfully']);
        }else{
            return json(['code'=>0,'data'=>[],'msg'=>'binding failed']);
        }


    }
    public function create_card_prize($id,$prize){
	   if(Db::name('member_card')->where(['id'=>$id,'status'=>1])->update([$prize=>1])){
	       return json(['code'=>1,'data'=>[],'msg'=>'Received successfully']);
       }
       return json(['code'=>0,'data'=>[],'msg'=>'operation failed']);
    }
    

    public function money_details($id){
        $map=['status'=>1];
        $start_time=input('start_time','');
        $end_time=input('end_time','');
        $act=input('act',0);

        if($start_time){
            $map['create_time']=['gt',strtotime($start_time)];
            $query['start_time']=$start_time;
        }

        if($end_time){
            $map['create_time']=['lt',strtotime($end_time)];
            $query['end_time']=$end_time;
        }

        if($start_time && $end_time){
            $map['create_time']=['BETWEEN',[strtotime($start_time),strtotime($end_time)]];
        }
        if($act){
            $map['act']=$act;
        }


        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('act', $act);

        $Users=new Users();
        $res = $Users->userMoneyLog($id,$map);

        $this->assign('lists',is_array($res['data'])?$res['data']:[]);
        return $this->fetch();
    }
    public function integral_details($uid = 0){
        $map[]=['think_integral_log.status','=',1];
	    if($uid){
            $map[]=['think_integral_log.uid','=',$uid];
        }


        $start_time=input('start_time','');
        $end_time=input('end_time','');
        $keywords=input('keywords','');
        $act=input('act',0);
        if($keywords){
            $map[]=['think_integral_log.uid|think_member.account|think_member.nickname','like','%'.$keywords.'%'];
        }
        if($start_time){
            $map[]=['think_integral_log.create_time','>',strtotime($start_time)];
        }
        if($end_time){
            $map[]=['think_integral_log.create_time','<',strtotime($end_time)];
        }
        if($start_time && $end_time){
            $map[]=['think_integral_log.create_time','BETWEEN',[strtotime($start_time),strtotime($end_time)]];
        }
        if($act){
            $map[]=['think_integral_log.act','=',$act];
        }
        $atc_list = Db::name('integral_log')->group('act')->where([['status','=',1]])->column('remark','act');
        $IntegralLog=new IntegralLog();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',10);
        $lists =$IntegralLog->join('think_member','think_integral_log.uid = think_member.id','left')->order('id desc')->field('think_member.account,think_member.nickname,think_integral_log.id,think_integral_log.num,think_integral_log.act,think_integral_log.remark,think_integral_log.update_time,think_integral_log.uid')->page($Nowpage,$limits)->where($map)->select();
        $count =$IntegralLog->join('think_member','think_integral_log.uid = think_member.id','left')->where($map)->count();
        return json(['code'=>1,'data'=>['atc_list'=>$atc_list,'count'=>$count,'lists'=>$lists],'msg'=>'']);

    }
    public function tianyuan_details(){
        $map=[];
        $uid=input('uid','');
        $start_time=input('start_time','');
        $end_time=input('end_time','');
        $keywords=input('keywords','');
        $act=input('act',0);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',10);

        if($uid){
            $map[]=['think_tianyuan_log.uid','=',$uid];
        }

        if($keywords){
            $map[]=['think_tianyuan_log.uid|think_member.account|think_member.nickname','like','%'.$keywords.'%'];
        }
        if($start_time){
            $map[]=['think_tianyuan_log.create_time','>',strtotime($start_time)];
        }
        if($end_time){
            $map[]=['think_tianyuan_log.create_time','<',strtotime($end_time)];
        }
        if($start_time && $end_time){
            $map[]=['think_tianyuan_log.create_time','BETWEEN',[strtotime($start_time),strtotime($end_time)]];
        }
        if($act){
            $map[]=['think_tianyuan_log.type','=',$act];
        }
        $TianyuanLog = new TianyuanLog();
        $lists =  $TianyuanLog->where($map)->join('think_member','think_tianyuan_log.uid = think_member.id','left')->order('id desc')->field('think_member.account,think_member.nickname,think_tianyuan_log.id,think_tianyuan_log.num,think_tianyuan_log.uid,think_tianyuan_log.type,think_tianyuan_log.remark,think_tianyuan_log.update_time')->page($Nowpage,$limits)->select();

        $count =$TianyuanLog->where($map)->join('think_member','think_tianyuan_log.uid = think_member.id','left')->count();
        $atc_list = Db::name('tianyuan_log')->group('type')->column('remark','type');

        return json(['code'=>1,'data'=>['atc_list'=>$atc_list,'count'=>$count,'lists'=>$lists],'msg'=>'']);
    }

    //*********************************************In-site message push*********************************************//
	//push list
	public function msg_list(){
		$list = Db::name('system_msg')->order('send_time desc')->paginate(10);
		$page = $list->render();
		if($list){
			$list = collection($list->items())->toArray();
			foreach($list as $k=>$v){
				$list[$k]['content'] = unserialize($v['content']);
				$list[$k]['date'] = date('Y-m-d',$v['send_time']);
			}
		}	
		$this->assign('list', $list);
        $this->assign('page', $page);
		return $this->fetch();
	}
	//add push
	public function add_msg(){
		if (Request::instance()->isPost()){
			$data = input('post.');
			//Insert a push data into the push table
			$id = Db::name('system_msg')->insertGetId([
												'receive'	=> $data['receive'],
												'title'		=> $data['title'],
												'content'	=> serialize($data['content']),
												'send_time'	=> time()
												]);
			//Add information to the user table according to the push method
			$back = ['code'=>0,'msg'=>'Push failed'];
			$uid = get_uid();
			if($id > 0){
				if($data['receive_type'] == 1){
					//推送给个人
					$res = Db::name('member_msg')->insert([
														'receive_uid'	=> $data['receive_id'],
														'send_uid'		=> $uid,
														'content_id'	=> $id,
														'status'		=> 0
														]);
					if($res > 0){
					$back = ['code'=>1,'msg'=>'successful push'];
					}
					}elseif($data['receive_type'] == 2){
					// Push to members of a group
					$member_list = Db::name('member')->field('id')->where('group_id',$data['receive_id'])->where('closed',0)->select( );
					$data_arr = [];
					foreach($member_list as $k=>$v){
					$data_arr[] = ['receive_uid'=>$v['id'],'send_uid'=>$uid,'content_id'=>$id,'status'=> 0];
					}
					$res = Db::name('member_msg')->insertAll($data_arr);
					if($res > 0){
					$back = ['code'=>1,'msg'=>'successful push'];
					}
				}
			}
			
			echo json_encode($back);
		}else{
			//get member group
			$user_group = Db::name('member_group')->where('status',1)->field('id,group_name')->select();
			$this->assign('user_group',json_encode($user_group));
			return $this->fetch();
			}
			}
				//delete
			public function del_msg(){
			$id = input('post.id');
			echo Db::name('system_msg')->where('id',$id)->delete();
			}
			
			//search for members
	public function search_member(){
		$keywords = input("post.keywords");
		$res = Db::name('member')->field('id,nickname')->where('account|nickname','like','%'.$keywords.'%')->where('closed',0)->limit(10)->select();
		echo json_encode($res);
	}
	
	
}