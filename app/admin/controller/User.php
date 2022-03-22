<?php

namespace app\admin\controller;
use app\admin\model\UserModel;
use app\admin\model\UserType;
use think\exception\ValidateException;
use think\facade\Db;

class User extends Base
{

    public function index(){
        $key = input('keyWords');
        $map[] =['think_admin.shop_id','=',0] ;
        if($key&&$key!=="")
        {
            $map[] = ['username','like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count = Db::name('admin')->where($map)->count();//Calculate total pages
        $user = new UserModel();
        $lists = $user->getUsersByWhere($map, $Nowpage, $limits);
        return json(['code'=>1,'data'=>['lists'=>$lists,'count'=>$count],'msg'=>'']);

    }

    //get role
    public function getRole(){
        $role = new UserType();
        return json(['code'=>1,'data'=>$role->getRole(),'msg'=>'']);
    }


    public function userAdd()
    {
        if(request()->isPost()){
            $param = input('post.');
            $param['password'] = md5(md5($param['password']) . config('app.auth_key'));
            try {
                $this->validate($param,'UserValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code'=>0,'data'=>[],'msg'=>$e->getError()]);
            }
            $user = new UserModel();
            $param['shop_id']=0;
            $flag = $user->insertUser($param);
            $accdata = array(
                'uid'=> $user['id'],
                'group_id'=> $param['groupid'],
            );
            $group_access = Db::name('auth_group_access')->insert($accdata);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $role = new UserType();
        return json(['code'=>1,'data'=>['role'=>$role->getKeyVal(['shop_id'=>0])],'msg'=>'']);
    }



    public function userEdit()
    {
        $user = new UserModel();

        if(request()->isPost()){

            $param = input('post.');

            try {
                $this->validate($param,'UserValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code'=>0,'data'=>[],'msg'=>$e->getError()]);
            }
            if(empty($param['password'])){
                unset($param['password']);
            }else{
                $param['password'] = md5(md5($param['password']) . config('app.auth_key'));
            }
            $map['shop_id']=0;
            $map['id']=$param['id'];
            $groupid = $param['groupid'];
            unset($param['groupid']);
            $flag = $user->editUser($param,$map);
            $group_access = Db::name('auth_group_access')->where('uid', $user['id'])->update(['group_id' => $groupid]);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $role = new UserType();
        return json(['code'=>1,'data'=>['info'=>$user->getOneUser(['id'=>$id]),'role'=>$role->getKeyVal(['shop_id'=>0])],'msg'=>'']);
    }



    public function userDel()
    {
        $id = input('param.id');
        $role = new UserModel();
        $map=[];
        $map['shop_id']=0;
        $map['id']=$id;
        $flag = $role->delUser($map);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }




    public function userState()
    {
        $id = input('param.id');
        $status = Db::name('admin')->where('id',$id)->value('status');//Judging the current state
        if($status==1)
        {
            $flag = Db::name('admin')->where('id',$id)->update(['status'=>0]);
			return json(['code' => 1, 'data' => [], 'msg' => 'disabled']);
        }
        else
        {
            $flag = Db::name('admin')->where('id',$id)->update(['status'=>1]);
            return json(['code' => 1, 'data' => [], 'msg' => 'enabled']);
        }
    
    }

}