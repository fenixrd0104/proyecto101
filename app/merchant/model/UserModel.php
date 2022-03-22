<?php

namespace app\merchant\model;
use think\exception\PDOException;
use think\Model;
use think\facade\Db;

class UserModel extends Model
{
    protected $name = 'admin';

    public static function onAfterRead($user)
    {

        if ('thinkphp' == $user->name) {
            return false;
        }
    }
    /**
     * Get user list information based on search criteria
     */
    public function getUsersByWhere($map, $Nowpage, $limits)
    {
        return $this->field('think_admin.*,think_auth_group.title')->join('think_auth_group_access','think_auth_group_access.uid=think_admin.id','left')->join('think_auth_group','think_auth_group. id=think_auth_group_access.group_id','left')->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }

    public function getShopUsersByWhere($map, $Nowpage, $limits)
    {
        return $this->field('think_admin.*,think_shop_lists.name shop_name')->join('think_shop_lists','think_admin.shop_id = think_shop_lists.id','left')->where($map)->page ($Nowpage, $limits)->order('id desc')->select();
    }

    /**
     * Get the number of all users based on search criteria
     * @param $where
     */
    public function getAllUsers($where)
    {
        return $this->where($where)->count();
    }

    /**
     * Insert administrator information
     * @param $param
     */
    public function insertUser($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'user['.$param['username'].'] added successfully',1);
                return ['code' => 1, 'data' => '', 'msg' => 'Add user successfully'];
            }
        }catch(\Exception $e){

            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }

    }

    /**
     * Edit administrator information
     * @param $param
     */
    public function editUser($param,$map)
    {
        try{
            $result =  $this->where($map)->update($param);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'用户【'.$param['username'].'】编辑成功',1);
                return ['code' => 1, 'data' => '', 'msg' => 'edit user successfully'];
            }
        }catch(PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    /**
     * Get role information based on administrator id
     * @param $id
     */
    public function getOneUser($map)
    {
        return $this->where($map)->find();
    }


    /**
     * delete admin
     * @param $id
     */
    public function delUser($map)
    {
        try{

            $this->where($map)->delete();
            Db::name('auth_group_access')->where('uid', $map['id'])->delete();
            writelog(session('uid'), session('username'), 'user['.session('username').'] delete administrator successfully (ID='.$map['id'].')' ,1);
            return ['code' => 1, 'data' => '', 'msg' => 'Delete user successfully'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}