<?php
namespace app\admin\controller;
use app\admin\model\Node;
use think\facade\Db;
class Index extends Base
{

    public function getMenu(){
        $node = new Node();
        return json(['code'=>1,'data'=>$node->getMenu(0,session('role_rule')),'msg'=>'']);
    }
    public function getInfo(){
       $node = new Node();
       $user_info = [
           'uid' => session('uid'),
           'username' => session('username'),
           'portrait' => session('role_title'),
           'rolename' => session('role_name'),
           'menu' => $node->getMenu($this->shopId,session('role_rule'))
       ];
       return json(['code'=>1,'data'=>$user_info,'msg'=>'']);
    }

    /**
     * [userEdit modify password]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function editpwd(){

        if(request()->isPost()){
            $param = input('post.');
            if(empty($param['password'])){
                return json(['code'=>0,'data'=>[],'msg'=>'New password cannot be empty']);
            }
            $user=Db::name('admin')->where('id='.session('uid'))->find();
            if(md5(md5($param['old_password']) .config('app.auth_key'))!=$user['password']){
               return json(['code' => 0, 'data' => '', 'msg' => 'old password error']);
            }else{
                $pwd['password']=md5(md5($param['password']) .config('app.auth_key'));
                Db::name('admin')->where('id='.$user['id'])->update($pwd);
                session(null);
                cache('db_config_data',null);//Clear the website configuration information in the cache
                return json(['code' => 1, 'data' => '', 'msg' => 'Password changed successfully']);
            }
        }

    }


    /**
     * clear cache
     */
    public function clear() {

        $runtime1 =App()->getRootPath().'runtime/session/';
        $runtime2 =App()->getRootPath().'runtime/wap/';
        $runtime3 =App()->getRootPath().'runtime/admin/';
        if (delete_dir_file($runtime1) && delete_dir_file($runtime2) && delete_dir_file($runtime3)) {
            return json(['code' => 1, 'msg' => 'Clear cache successfully']);
        } else {
            return json(['code' => 0, 'msg' => 'Failed to clear cache']);
        }
    }


}
