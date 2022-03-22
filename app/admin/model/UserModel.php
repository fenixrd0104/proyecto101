<?php

namespace app\admin\model;
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
     * 根据搜索条件获取用户列表信息
     */
    public function getUsersByWhere($map, $Nowpage, $limits)
    {
        return $this->field('think_admin.*,think_auth_group.title')->join('think_auth_group_access','think_auth_group_access.uid=think_admin.id','left')->join('think_auth_group','think_auth_group.id=think_auth_group_access.group_id','left')->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }

    public function getShopUsersByWhere($map, $Nowpage, $limits)
    {
        return $this->field('think_admin.*,think_shop_lists.name shop_name')->join('think_shop_lists','think_admin.shop_id = think_shop_lists.id','left')->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }

    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllUsers($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入管理员信息
     * @param $param
     */
    public function insertUser($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'用户【'.$param['username'].'】添加成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '添加用户成功'];
            }
        }catch(\Exception $e){

            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }

    }

    /**
     * 编辑管理员信息
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
                return ['code' => 1, 'data' => '', 'msg' => '编辑用户成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    /**
     * 根据管理员id获取角色信息
     * @param $id
     */
    public function getOneUser($map)
    {
        return $this->where($map)->find();
    }


    /**
     * 删除管理员
     * @param $id
     */
    public function delUser($map)
    {
        try{

            $this->where($map)->delete();
            Db::name('auth_group_access')->where('uid', $map['id'])->delete();
            writelog(session('uid'),session('username'),'用户【'.session('username').'】删除管理员成功(ID='.$map['id'].')',1);
            return ['code' => 1, 'data' => '', 'msg' => '删除用户成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}