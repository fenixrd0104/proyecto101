<?php
namespace app\admin\controller;

use think\facade\Db;
use app\common\model\Member;
use app\common\model\MemberWalletLogModel;

class Assignment extends Base{

/*--------------------------------------------Message management---- -------------------------------------------------- --------------------------------------------*/
    //Message List
    public function index_book(){
        $status = input('param.status','');
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $map = [];
        if($status!==''){
            $map[]=['status','=',$status];
        }
        $count=Db::name('guestbook')->where($map)->count();
        $list=Db::name('guestbook')->where($map)->order('id desc')->page($Nowpage,$limits)
            ->select();
        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list]]);
    }

    //Message review
    public function booktype(){
        $id = input('param.id');
        $status = input('param.status');
        $flag = Db::name('guestbook')
            ->where('id', $id)
            ->find();
        if($flag){
            if($status==$flag['status']){
				return json(['code' => 0, 'data' => '', 'msg' => 'error operation']);
            }else{
                Db::name('guestbook')->where('id', $id)->update(['status'=>$status]);
                return json(['code' => 1, 'data' => '', 'msg' => 'Message review operation succeeded']);
            }
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => 'The message does not exist']);
        }
    }


 // delete message
    public function delBookItem(){
        $id = input('param.id');
        $flag = Db::name('guestbook')
            ->where('id', $id)
            ->delete();
        if($flag){
            return json(['code' => 1, 'data' => '', 'msg' => 'Delete successful']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => 'deletion failed']);
        }
    }



/*------------------------------------------------------Task management---- -------------------------------------------------- --------------------------------------------*/
    //daily tasks
    public function index_today(){
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count=Db::name('assignment')->where('type','=',1)->count();
        $list=Db::name('assignment')->where('type','=',1)->order('id desc')->page($Nowpage,$limits)
            ->select();
        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list]]);
    }

    //challenge mission
    public function index_challenge(){
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count=Db::name('assignment')->where('type','=',2)->count();
        $list=Db::name('assignment')->where('type','=',2)->order('id desc')->page($Nowpage,$limits)
            ->select();
        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list]]);
    }

// delete the task
    public function delItem(){
        $id = input('param.id');
        $flag = Db::name('assignment')
            ->where('id', $id)
            ->delete();
        if($flag){
            return json(['code' => 1, 'data' => '', 'msg' => 'Delete successful']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => 'deletion failed']);
        }
    }

    /**
     * [article_state task state]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function stateAssign()
    {
        $id=input('param.id');
        $status = Db::name('assignment')->where(array('id'=>$id))->value('status');//Judging the current state
        if($status==1)
        {
            $flag = Db::name('assignment')->where(array('id'=>$id))->update(['status'=>0]);
			return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        }
        else
        {
            $flag = Db::name('assignment')->where(array('id'=>$id))->update(['status'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'enabled']);
        }

    }



 //add challenge task
    public function addPost(){
        if(request()->isPost()){
            $param = input('post.');
            $param['content'] = input('post.content','',null);
            if(!$param['title']){
                return json(['code' => 0, 'msg' => 'Please enter the title']);
            }
            if(!$param['content']){
                return json(['code' => 0, 'msg' => 'Please enter the details of the challenge task']);
            }
            if(!$param['rw_hour'] || $param['rw_hour']<=0){
                return json(['code' => 0, 'msg' => 'The challenge task execution time must be greater than 0']);
            }
            if(!$param['integral'] || $param['integral']<0){
                return json(['code' => 0, 'msg' => 'Challenge task consumption points must be greater than or equal to 0']);
            }
            if(!$param['reward'] || $param['reward']<0){
                return json(['code' => 0, 'msg' => 'The challenge task reward KFC must be greater than or equal to 0']);
            }
            $param['created_at'] = time();
            $param['type'] = 2;
            $param['jl_type'] = 0;
            $param['jl_integral'] = 0;
            $param['video_url'] = '';
            $param['read_time'] = 0;

            $article = new \app\admin\model\Assignment();
            $flag = $article->insertArticle($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        return json(['code'=>1,'data'=>[],'msg'=>'']);
    }

	//Add daily tasks
    public function addDayPost(){
        if(request()->isPost()){
            $param = input('post.');
            $param['content'] = input('post.content','');
            if(!$param['title']){
                return json(['code' => 0, 'msg' => 'Please enter the title']);
            }
            if(!$param['content']){
                return json(['code' => 0, 'msg' => 'Please enter daily task details']);
            }
            if(!$param['jl_integral'] || $param['jl_integral']<0){
                return json(['code' => 0, 'msg' => 'Daily task reward points must be greater than or equal to 0']);
            }
            if(!$param['jl_type'] || $param['jl_type']<=0){
                return json(['code' => 0, 'msg' => 'Please select the correct daily task type']);
            }
            $nums = Db::name('assignment')->where(['jl_type'=>$param['jl_type']])->count();
            if($nums>0 && $param['jl_type']!=2){
                return json(['code' => 0, 'msg' => 'Only one can be added to this task type']);
            }
            $param['created_at'] = time();
            $param['type'] = 1;
            $param['rw_hour'] = 0;
            $param['integral'] = 0;
            $param['reward'] = 0;
            $article = new \app\admin\model\Assignment();
            $flag = $article->insertArticle($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        return json(['code'=>1,'data'=>[],'msg'=>'']);
    }


    //Edit tasks
    public function editassign($id){
        $assigninfo = Db::name('assignment')->where(['id'=>$id])->find();
        if(!$assigninfo){
            return json(['code'=>0,'data'=>[],'msg'=>'']);
        }
        if(request()->isPost()){
            $param = input('post.');
            if($assigninfo['type']==1){

                $nums = Db::name('assignment')->where([['jl_type','=',$param['jl_type']],['id','<>',$id]])->count();
                if($nums>0 && $param['jl_type']!=2){
			return json(['code' => 0, 'msg' => 'The task type can only be one']);
                }

                if(!$param['title']){
                    return json(['code' => 0, 'msg' => 'Please enter the title']);
                }
                if(!$param['content']){
                    return json(['code' => 0, 'msg' => 'Please enter daily task details']);
                }
                if(!$param['jl_integral'] || $param['jl_integral']<0){
                    return json(['code' => 0, 'msg' => 'Daily task reward points must be greater than or equal to 0']);
                }
                if(!$param['jl_type'] || $param['jl_type']<=0){
                    return json(['code' => 0, 'msg' => 'Please select the correct daily task type']);
                }

            }else{
                if(!$param['title']){
                    return json(['code' => 0, 'msg' => 'Please enter the title']);
                }
                if(!$param['content']){
                    return json(['code' => 0, 'msg' => 'Please enter the details of the challenge task']);
                }
                if(!$param['rw_hour'] || $param['rw_hour']<=0){
                    return json(['code' => 0, 'msg' => 'The challenge task execution time must be greater than 0']);
                }
                if(!$param['integral'] || $param['integral']<0){
                    return json(['code' => 0, 'msg' => 'Challenge task consumption points must be greater than or equal to 0']);
                }
                if(!$param['reward'] || $param['reward']<0) {
                    return json(['code' => 0, 'msg' => 'The challenge task reward KFC must be greater than or equal to 0']);
                }
            }

            Db::name('assignment')->update($param);

            return json(['code'=>1,'data'=>$assigninfo,'msg'=>'Task edited successfully']);

        }else{
            return json(['code'=>1,'data'=>$assigninfo,'msg'=>'']);
        }


    }



//----------------------------Activity, Donation, Business--------------- -------------------------------------------------- --------------------

    //Participate in the event member information
    public function huodong(){
        $account = input('account','');
        $uid = input('uid','');
        $map = [];
        if($uid && $uid!=''){
            $map[] = ['g.uid','=',$uid];
        }
        if($account && $account!=''){
            $map[] = ['m.account','like',$account];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count=Db::name('sign_up')->alias('g')
            ->join('member m','g.uid=m.id')->where($map)->count();

        $list=Db::name('sign_up')->alias('g')
            ->join('member m','g.uid=m.id')
            ->field("g.*,m.nickname,m.account")
            ->where($map)
            ->order('g.id desc')->page($Nowpage,$limits)
            ->select();

        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list]]);
    }


    //Donation List
    public function juankuan(){
        $account = input('account','');
        $uid = input('uid','');
        $map = [];
        if($uid && $uid!=''){
            $map[] = ['g.uid','=',$uid];
        }
        if($account && $account!=''){
            $map[] = ['m.account','like',$account];
        }
        $map[] = ['g.type','=',200];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count=Db::name('member_wallet_log')->alias('g')
            ->join('member m','g.uid=m.id')->where($map)->count();

        $list=Db::name('member_wallet_log')->alias('g')
            ->join('member m','g.uid=m.id')
            ->field("g.id,g.uid,g.number,g.z_remarks,g.create_time,m.nickname,m.account")
            ->where($map)
            ->order('id desc')->page($Nowpage,$limits)
            ->select();

        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list]]);
    }

    //Business cooperation list
    public function shangwu(){
        $account = input('account','');
        $uid = input('uid','');
        $map = [];
        if($uid && $uid!=''){
            $map[] = ['g.uid','=',$uid];
        }
        if($account && $account!=''){
            $map[] = ['m.account','like',$account];
        }

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count=Db::name('business_cooperation')->alias('g')
            ->join('member m','g.uid=m.id')->where($map)->count();

        $list=Db::name('business_cooperation')->alias('g')
            ->join('member m','g.uid=m.id')
            ->field("g.*,m.nickname,m.account")
            ->where($map)
            ->order('g.id desc')->page($Nowpage,$limits)
            ->select();

        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list]]);
    }



//--------------------------------------Task log------------ -------------------------------------------------- -----------------------

    // daily task list
    public function daytask(){

        $account = input('account','');
        $title = input('title','');
        $uid = input('uid','');
        $status = input('status','');
        $map = [];
        $map[] = ['task_type','=',1];
        if($uid && $uid!=''){
            $map[] = ['uid','=',$uid];
        }
        if($status!=''){
            $map[]=['status','=',$status];
        }
        if($account && $account!=''){
            $map[] = ['account','like',$account];
        }
        if($title && $title!=''){
            $map[] = ['title','like',$title];
        }

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count=Db::name('task_log')->where($map)->count();

        $list=Db::name('task_log')->where($map)
            ->order('id desc')->page($Nowpage,$limits)
            ->select()->toArray();
        foreach ($list as $keys=>$info){
            if($info['jl_type']==1){
				$type_name = 'Sign in task';
            }elseif ($info['jl_type']==2){
                $type_name = 'Video Task';
            }elseif ($info['jl_type']==3){
                $type_name = 'Promotion Task';
            }elseif ($info['jl_type']==4){
                $type_name = 'Payment task';
            }elseif ($info['jl_type']==5){
                $type_name = 'Auction Task';
            }elseif ($info['jl_type']==6){
                $type_name = 'Top up task';
            }elseif ($info['jl_type']==7){
                $type_name = 'Reading task';
            }
            $list[$keys]['type_name'] = $type_name;
        }

        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list]]);
    }


    //Challenge task list
    public function pktask(){

        $account = input('account','');
        $title = input('title','');
        $uid = input('uid','');
        $status = input('status','');
        $map = [];
        $map[] = ['task_type','=',2];
        if($uid && $uid!=''){
            $map[] = ['uid','=',$uid];
        }
        if($status!=''){
            $map[]=['status','=',$status];
        }
        if($account && $account!=''){
            $map[] = ['account','like',$account];
        }
        if($title && $title!=''){
            $map[] = ['title','like',$title];
        }


        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count=Db::name('task_log')->where($map)->count();

        $list=Db::name('task_log')->where($map)
            ->order('id desc')->page($Nowpage,$limits)
            ->select();

        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list]]);

    }

	//challenge task review
    public function taskstatus($id,$status,$beizhu=''){

        $taskinfo = Db::name('task_log')->where(['id'=>$id,'task_type'=>2])->find();
        if(!$taskinfo){
            return json(['code'=>0,'msg'=>'error operation']);
        }
        if($taskinfo['status']!=1){
            return json(['code'=>0,'msg'=>'The task is not in review state and cannot be operated']);
        }

        $tmember = Db::name('member')->where(['id'=>$taskinfo['uid']])->find();
        if($status==2){
            if(!$beizhu){
                $beizhu='Challenge Review Judgment Completed';
            }
		//change KRC
            Member::Onefield($tmember['id'], 'money', 'up', $taskinfo['reward_num']);
            //add log
            MemberWalletLogModel::log($tmember['id'], $taskinfo['reward_num'], $tmember['money'], $tmember['money'] + $taskinfo['reward_num'], 201, 'Challenge task Success reward', $taskinfo['task_id']);

        }elseif($status==3){
            if(!$beizhu){
                $beizhu='Challenge audit judgment failed';
            }
        }else{
            return json(['code'=>0,'msg'=>'error operation']);
        }
        //modify challenge status
        Db::name('task_log')->where(['id'=>$id,'task_type'=>2])->update(['status'=>$status,'beizhu'=>$beizhu ]);

        return json(['code'=>1,'msg'=>'challenge review operation successful']);





    }






}