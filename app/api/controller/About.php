<?php
namespace app\api\controller;

use app\common\service\Users;
use think\facade\Db;
use app\common\model\Member;
use app\common\model\MemberWalletLogModel;

class About extends Base{
    /**
     * About us page
     */
    public function us(){
        $ad_list = get_adinfo(62,$type='list');
        $note = get_adinfo(61,$type='single');
        $article = get_adinfo(62,$type='article');
        $info = [
            'slide'=>$ad_list,
            'note'=>$note,
            'article'=>$article,
        ];
        return json(['status'=>1,'data'=>$info,'msg'=>'']);
    }

    /**
     * Member Information
     */
    public function member($type){
        //var_dump('jjj');
        if($type==1){
            $cat_id = 4;//Member activity
        }elseif ($type==2){
            $cat_id = 6;//Member story
        }elseif ($type==3){
            $cat_id=7;//Member commitment
        }elseif ($type==4){
            $cat_id=9;//Project system
        }elseif ($type==5){
            $cat_id=10;//WE Q&A
        }elseif ($type==6){
            $cat_id=11;//Social attention
        }elseif ($type==7){
            $cat_id=12;//WE honor
        }else{
            $cat_id = 1;
        }
        $article = Db::name('article')->where([['status','=',1],['cate_id','=',$cat_id]])->order(['is_tui'=>'desc','id'=>'desc'])->select();
        return json(['status'=>1,'data'=>$article,'msg'=>'']);
    }

    /**
     *message
     */
    public function add_book(){
        $content = $_REQUEST['content'];
        $images = $_REQUEST['images'];
        $user_id = $this->uid();
//        $user_id = 1;

        /*if(is_array($images)){
            $str = json_encode($images);
        }else{
            $str = $images;
        }*/
        $str = explode(',',$images);
        $str = json_encode($str);
        if(!$content){
			return json(['status' => 0, 'msg' => 'Content cannot be empty']);
        }
        $add = [
            'content'=>$content,
            'images'=>$str,
            'create_time'=>date("Y-m-d H:i:s"),
            'uid'=>$user_id
        ];
        $res = Db::name('guestbook')->insert($add);

        if($res){
            return json(['status' => 1, 'msg' => 'Message successful']);
        }else{
            return json(['status' => 0, 'msg' => 'Message submission failed']);
        }
    }

    //Message List
    public function book_list($page=1, $limit=8){
        // $user_id = $this->uid();
        $list = Db::name('guestbook')->alias('g')
            ->join('member m','g.uid=m.id')
            ->field("g.*,m.nickname,m.head_img")
            ->where(['g.status'=>1])
            ->order('id desc')->page($page,$limit)
            ->select();
        $tmp = [];
        foreach ($list as $key=>&$val){
            $val['images'] = json_decode($val['images']);
            $tmp[$key] = $val;
        }
        return json(['status' => 1,'data'=>['list'=>$list], 'msg' => 'Get data successfully']);
    }

    /**
     * Promotion list
     */
    public function extend_list($page = 1, $num = 10){
        //$user_id = $this->uid();
        $info = Db::name('popularize')->order('id desc')->page($page, $num)->select();
        return json(['status'=>1,'data'=>$info,'msg'=>'']);
    }

    /**
     * Sign up for the event
     */
    public function sign_up(){
        $name = $_REQUEST['name'];
        $mobile = $_REQUEST['mobile'];
        $city = $_REQUEST['city'];
        if(!$name){
            return json(['status' => 0, 'msg' => 'Name cannot be empty']);
        }
        if(!$mobile){
            return json(['status' => 0, 'msg' => 'Mobile phone number cannot be empty']);
        }
        if(!$city){
            return json(['status' => 0, 'msg' => 'City cannot be empty']);
        }
        $user_id = $this->uid();
        $add = [
            'name'=>$name,
            'mobile'=>$mobile,
            'create_time'=>date("Y-m-d H:i:s"),
            'uid'=>$user_id
        ];
        $res = Db::name('sign_up')->insert($add);
        if($res){
            return json(['status' => 1, 'msg' => 'registered successfully']);
        }else{
            return json(['status' => 0, 'msg' => 'registration failed']);
        }

    }

    /**
     * Donate
     */
    public function contribute_money(){
        $money = $_REQUEST['money'];
        $pay_password = $_REQUEST['pay_password'];
        if(!$money && $money>0){
            return json(['status' => 0, 'msg' => 'The amount cannot be empty and must be greater than 0']);
        }
        if(!$pay_password){
            return json(['status' => 0, 'msg' => 'payment password cannot be empty']);
        }
        //payment password
        $jypwd=base64_encode(md5($pay_password,true));

        $user_id = $this->uid();
        $member = Db::name('member')->where([['id','=',$user_id]])->find();
        if(empty($member['pay_password'])){
            return json(['status'=>0,'data'=>[],'msg'=>'No transaction password is set']);
        }
        if($jypwd!=$member['pay_password']){
            return json(['status'=>0,'data'=>[],'msg'=>'payment password is incorrect']);
        }

        if($member['money']<$money){
            return json(['status' => 0, 'msg' => 'Insufficient balance']);
        }

        //Donation minus balance
        Member::Onefield($user_id,'money','down',$money);
        $res = MemberWalletLogModel::log($user_id,$money,$member['money'],$member['money']-$money,200,'Dream Fund Donation',0);


        if($res){
            return json(['status' => 1, 'msg' => 'Donation successful']);
        }else{
            return json(['status' => 0, 'msg' => 'donation failed']);
        }
    }

    /**
     * Dream Fund
     */
    public function foundation(){
        $jjlist = get_adinfo(63,$type='list');
        $num = Db::name('member_wallet_log')->where([['type','=',200]])->sum("number");
        $cat_id = 8;
        $article = Db::name('article')->where([['status','=',1],['cate_id','=',$cat_id]])->order(['is_tui'=>'desc','orderby'=>'asc','create_time'=>'desc'])->select();
        //return json(['status'=>1,'data'=>$article,'msg'=>'']);
        return json(['status' => 1,'data'=>['total'=>$num,'article'=>$article,'jjlist'=>$jjlist], 'msg' => '']);
    }

    /**
     * Business Cooperation
     */
    public function business_cooperation(){

        $name = isset($_REQUEST['name'])?$_REQUEST['name']:"";
        $mobile = isset($_REQUEST['mobile'])?$_REQUEST['mobile']:"";
        $email = isset($_REQUEST['email'])?$_REQUEST['email']:"";
        $note = isset($_REQUEST['note'])?$_REQUEST['note']:"";
        $user_id = $this->uid();
        //$user_id = 1;

        if(!$name){
            return json(['status' => 0, 'msg' => 'Name cannot be empty']);
        }
        if(!$mobile){
            return json(['status' => 0, 'msg' => 'Mobile phone number cannot be empty']);
        }
        if(!$email){
            return json(['status' => 0, 'msg' => 'The mailbox cannot be empty']);
        }
        if(!$note){
            return json(['status' => 0, 'msg' => 'Message cannot be empty']);
        }
        $add = [
            'name'=>$name,
            'mobile'=>$mobile,
            'email'=>$email,
            'note'=>$note,
            'create_time'=>date("Y-m-d H:i:s"),
            'uid'=>$user_id
        ];
        $res = Db::name('business_cooperation')->insert($add);

        if($res){
            return json(['status' => 1, 'msg' => 'Submit successfully']);
        }else{
            return json(['status' => 0, 'msg' => 'submission failed']);
        }
    }



    /**
     * challenge mission
     */
    public function challenge(){
        $userid = $this->uid();
        $page = input("post.page",1);
        $limit = input("post.limit",150);
        $list = Db::name('assignment')
            ->where([['status','=',1],["type","=",2]])
            ->order('id desc')->page($page,$limit)
            ->select()->toArray();

        // $times = strtotime(date('Y-m-d'));
        foreach ($list as $key=>$val){
            $info = Db::name('task_log')->where([['task_id','=',$val['id']],['uid','=',$userid],['jl_type','=',$val['jl_type']]])->order('id desc')->find();
            if(!$info){
                $list[$key]['task_type'] = -1;
            }else{
                if($val['is_repeat']==0){
                    $list[$key]['task_type'] = $info['status'];	//0 challenge, click to complete 1 background review is over 2 challenge successful 3 challenge failed
                }else{
                    if($info['status']>0){
                        $list[$key]['task_type'] = -1;
                    }else{
                        $list[$key]['task_type'] = $info['status'];
                    }
                }
            }
        }
        return json(['status' => 1,'data'=>['list'=>$list], 'msg' => 'Get data successfully']);
    }


    /**
     * daily tasks
     */
    public function daily_tasks(){
        $userid = $this->uid();
        $page = input("post.page",1);
        $limit = input("post.limit",15);
        if($page>1){
            $list = [];
        }else {
            $list = Db::name('assignment')
                ->where([['status','=',1],["type", "=", 1]])
                ->order('id desc')->page($page, $limit)
                ->select()->toArray();
        }
        $times = strtotime(date('Y-m-d'));
        foreach ($list as $key=>$val){
            $info = Db::name('task_log')->where([['task_id','=',$val['id']],['uid','=',$userid],['create_time','>',$times],['jl_type','=',$val['jl_type']]])->find();

            if($val['jl_type']>0) {
                if ($info) {
                    if($info['status']==2){
                        $list[$key]['task_type'] = 2;
                    }else{
                        $list[$key]['task_type'] = 1;
                    }
                } else {
                    $list[$key]['task_type'] = 0;
                }
            }
            if($val['jl_type']==1){
				$list[$key]['type_name'] = 'Sign in task';
                if($info){
                    $list[$key]['task_type'] = 2;
                }else{
                    $list[$key]['task_type'] = 1;
                }
            }elseif ($val['jl_type']==2){
                $list[$key]['type_name'] = 'Video task';
            }elseif ($val['jl_type']==3){
                $list[$key]['type_name'] = 'Promotion task';
            }elseif ($val['jl_type']==4){
                $list[$key]['type_name'] = 'Payment task';
            }elseif ($val['jl_type']==5){
                $list[$key]['type_name'] = 'Auction Task';
            }elseif ($val['jl_type']==6){
                $list[$key]['type_name'] = 'Top-up task';
            }elseif ($val['jl_type']==7){
                $list[$key]['type_name'] = 'Reading tasks';
            }

        }
        return json(['status' => 1,'data'=>['list'=>$list], 'msg' => 'Get data successfully']);
    }




    /**
     * Daily quests to receive
     */
    public function daily_receive(){
        $userid = $this->uid();
        $id = input("id");
        if(!$id){
            return json(['status' => 0, 'msg' => 'error operation']);
        }
        $info = Db::name('assignment')->where([["type","=",1],['id','=',$id]])->find();
        if(!$info){
            return json(['status' => 0, 'msg' => 'The task does not exist']);
        }
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
        $times = strtotime(date('Y-m-d'));
        $taskinfo = Db::name('task_log')->where([['task_id','=',$info['id']],['uid','=',$userid],['create_time','>',$times],['jl_type','=',$info['jl_type']]])->find();
        if($taskinfo && $taskinfo['status']==2){
		return json(['status' => 0, 'msg' => 'The task you have received today']);
        }
        if(!$taskinfo && $info['jl_type']!=1){
            return json(['status' => 0, 'msg' => 'Please complete the task first']);
        }
        if($taskinfo && $taskinfo['jl_type']==1){
            return json(['status' => 0, 'msg' => 'You have checked in today']);
        }

        $memberinfo = Db::name('member')->field('jifen,account')->where('id',$userid)->find();
        Db::name('member')->where('id',$userid)->inc('jifen',$info['jl_integral'])->save();
        $add = [
            'user_id'=>$userid,
            'pay_points'=>$info['jl_integral'],
            'user_money'=>$memberinfo['jifen'],
            'order_sn'=>$info['id'],
            'order_id'=>$info['jl_type'],
            'change_time'=>time(),
            'desc'=>$info['title'].'('.$type_name.')',
            'type'=>1,//1 plus 2 minus
        ];
        $res = Db::name('account_log')->insert($add);
        if($res){
            if($info['jl_type']==1){
                $datas = [];
                $datas['uid'] = $userid;
                $datas['task_id'] = $info['id'];
                $datas['title'] = $info['title'];
                $datas['jl_type'] = $info['jl_type'];
                $datas['account'] = $memberinfo['account'];
                $datas['integral_num'] = $info['jl_integral'];
                $datas['status'] = 2;
                $datas['task_type'] = 1;
                $datas['create_time'] = time();
                $datas['end_time'] = time();
                Db::name('task_log')->insert($datas);
            }else{
                Db::name('task_log')->where(['id'=>$taskinfo['id']])->update(['status'=>2]);
            }
			return json(['status' => 1, 'msg' => 'task reward received successfully']);
        }else{
            return json(['status' => 0, 'msg' => 'task reward failed to receive']);
        }

    }

    /**
     * Receive challenge tasks
     */
    public function challenge_receive(){
        $userid = $this->uid();
        $id = input("id");
        if(!$id){
		return json(['status' => 0, 'msg' => 'error operation']);
        }
        $info = Db::name('assignment')->where([["type","=",2],['id','=',$id]])->find();
        if(!$info){
            return json(['status' => 0, 'msg' => 'The task does not exist']);
        }

        $taskinfo = Db::name('task_log')->where([['task_id','=',$info['id']],['uid','=',$userid],[' jl_type','=',$info['jl_type']]])->order('id desc')->find();
        if($taskinfo){
            if($info['is_repeat']==1){
                if($taskinfo['status']<2) {
                    return json(['status' => 0, 'msg' => 'The challenge task is not over, you cannot repeat the challenge']);
                }
            }else{
                return json(['status' => 0, 'msg' => 'The challenge task has already been received and cannot be challenged twice']);
            }
        }

        $memberinfo = Db::name('member')->field('jifen,account')->where('id',$userid)->find();
        if($memberinfo['jifen']<$info['integral']){
            return json(['status' => 0, 'msg' => 'Insufficient points, unable to challenge this task']);
        }
        $datas = [];
        $datas['uid'] = $userid;
        $datas['task_id'] = $info['id'];
        $datas['title'] = $info['title'];
        $datas['jl_type'] = $info['jl_type'];
        $datas['account'] = $memberinfo['account'];
        $datas['integral_num'] = 0;
        $datas['reward_num'] = $info['reward'];
        $datas['status'] = 0;
        $datas['task_type'] = 2;
        $datas['create_time'] = time();
        $datas['end_time'] = time()+$info['rw_hour']*3600;
        $rey = Db::name('task_log')->insert($datas);

        if($rey){
            Db::name('member')->where('id',$userid)->dec('jifen',$info['integral'])->save();
            $add = [
                'user_id'=>$userid,
                'pay_points'=>$info['integral'],
                'user_money'=>$memberinfo['jifen'],
                'order_sn'=>$info['id'],
                'order_id'=>$info['jl_type'],
                'change_time'=>time(),
                'desc'=>$info['title'].'(challenge task)',
                'type'=>2,//1 plus 2 minus
            ];
            $res = Db::name('account_log')->insert($add);
        }

        return json(['status' => 1,'data'=>[], 'msg' => 'Challenge task received successfully']);
    }


    //Challenge task completed and submitted
    public function taskok(){
        $userid = $this->uid();
        $id = input("id");

        if(Db::name('task_log')->where(['uid'=>$userid,'task_id'=>$id,'status'=>0,'task_type'=>2])->update(['status'=>1])){
			return json(['status' => 1,'data'=>[], 'msg' => 'The task is completed and submitted successfully, waiting for review']);
        }else{
            return json(['status' => 0,'data'=>[], 'msg' => 'operation failed']);
        }

    }


    //Challenge mission record list
    public function tasklist(){
        $userid = $this->uid();
        $page = input("post.page",1);
        $limit = input("post.limit",15);
        $list = Db::name('task_log')->alias('t')
            ->join('assignment a','a.id=t.task_id')
            ->field("t.*,a.content,a.cover,a.integral")
            ->where([["t.task_type","=",2],['t.uid','=',$userid]])
            ->order('id desc')->page($page,$limit)
            ->select()->toArray();

        return json(['status' => 1,'data'=>['list'=>$list], 'msg' => '']);
    }


    //Points log
    public function jifenlist(){
        $userid = $this->uid();
        $page = input("page",1);
        $limit = input("limit",15);
        $list = Db::name('account_log')
            ->field("log_id,pay_points,change_time,desc,type")
            ->where([['user_id','=',$userid]])
            ->order('log_id desc')->page($page,$limit)
            ->select()->toArray();

        return json(['status' => 1,'data'=>['list'=>$list], 'msg' => '']);

    }

    /*
     * Daily task completion interface (video and article)
     * When the article is id is 0 jl_type is 7
     * When video, the id is the task id and jl_type is 2
     */
    public function taskapi($id=0,$jl_type){
        $userid = $this->uid();
        $User = new Users();
        if($id==0){
            if($jl_type==7){
                $infos = Db::name('assignment')->where(['jl_type'=>$jl_type,'status'=>1])->find();
                if($infos){
                    $sss = $User->daytask($infos['id'],$userid);
                    return json($sss);

                }
            }
        }else{
            if($id && $jl_type==2){
                $infos = Db::name('assignment')->where(['id'=>$id,'jl_type'=>$jl_type,'status'=>1])->find();
                if($infos){
                    $sss = $User->daytask($infos['id'],$userid);
                    return json($sss);
                }
            }
        }
        return json(['status' => 0, 'msg' => 'wrong operation']);

    }



}