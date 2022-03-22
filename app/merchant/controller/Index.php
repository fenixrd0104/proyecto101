<?php
namespace app\merchant\controller;
use think\facade\Session;
use think\facade\Db;
class Index extends Base
{
	public function getInfo(){
       $user_info = [
           'uid' => Session::get('mid'),
           'username' => Session::get('nickname'),
       ];
       return json(['code'=>1,'data'=>$user_info,'msg'=>'']);

    }
    /**
     * clear cache
     */
    public function clear() {

        $runtime =App()->getRootPath().'runtime/';
        if (delete_dir_file($runtime)) {
            return json(['code' => 1, 'msg' => 'Clear cache successfully']);
        } else {
            return json(['code' => 0, 'msg' => 'Failed to clear cache']);
        }
    }

    public function editpwd(){

        if(request()->isPost()){
            $param = input('post.');
            if(empty($param['password'])){
                return json(['code'=>0,'data'=>[],'msg'=>'New password cannot be empty']);
            }
            $user=Db::name('merchant')->where('id='.session('uid'))->find();
            if(md5(md5($param['old_password']) .config('app.auth_key'))!=$user['password']){
               return json(['code' => 0, 'data' => '', 'msg' => 'old password error']);
            }else{
                $pwd['password']=md5(md5($param['password']) . config('app.auth_key'));
                Db::name('merchant')->where('id='.$user['id'])->update($pwd);
                session(null);
                cache('db_config_data',null);//Clear the website configuration information in the cache
                return json(['code' => 1, 'data' => '', 'msg' => 'Password changed successfully']);
            }
        }

    }
}