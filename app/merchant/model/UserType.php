<?php

namespace app\merchant\model;
use think\facade\Db;
use think\Model;

class UserType extends Model
{
    protected  $name = 'auth_group';

    // Enable automatic writing of timestamp fields
    protected $autoWriteTimestamp = true;

    /**
     * [getRoleByWhere gets role list information based on conditions]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getRoleByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }



    /**
     * [getRoleByWhere to get the number of all roles based on conditions]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getAllRole($where)
    {
        return $this->where($where)->count();
    }



    /**
     * [insertRole insert role information]
     * @author [Tian Jianlong] [864491238@qq.com]
     */    
    public function insertRole($param)
    {
        try{
            $result =  $this->save($param);
            if(false === $result){               
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'Add role successfully'];
            }
        }catch(PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editRole edit role information]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function editRole($map, $param)
    {
        try{
            $result = $this->where($map)->save($param);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'edit role successful'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneRole gets role information based on role id]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getOneRole($id)
    {
        return $this->where('id', $id)->find();
    }



    /**
     * [delRole delete role]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function delRole($map)
    {
        try{
            $this->where($map)->delete();
            return ['code' => 1, 'data' => '', 'msg' => 'Delete the role successfully'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getRole gets all role information]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getRole()
    {
        return $this->where('id','<>',1)->select();
    }


    /**
     * [getRole gets the permission node of the role]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getRuleById($id)
    {
        $res = $this->field('rules')->where('id', $id)->find();
        return $res['rules'];
    }


    /**
     * [editAccess assign permissions]
     * @author [Tian Jianlong] [864491238@qq.com]
     */ 
    public function editAccess($map,$param)
    {
        try{
            $this->where($map)->save($param);
           return ['code' => 1, 'data' => '', 'msg' => 'Permission assignment succeeded'];

        }catch(PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }




    /**
     * [getRoleInfo gets role information]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getRoleInfo($uid){
        //Get all groups of the user first
        $group_ids = Db::name('auth_group_access')->where(['uid'=>$uid])->column('group_id');

        $result = Db::name('auth_group')->where('id', 'in',$group_ids)->where(['status'=>1])->select();

        $retrun  = [
            'title'=>'',
            'rules'=>[],
            'name'=>[],
        ];

        foreach ($result as $v){
            if(empty($v['rules'])){
                $rule = [];
            }else{
                $rule = explode(',',$v['rules']);
            }
            $retrun['rules']=array_merge($retrun['rules'],$rule);
            $retrun['title'].=$v['title'].',';
        }
        $retrun['title']=trim( $retrun['title'],',');

        if(empty($retrun['rules'])){
            $where = [];
        }else{
            $where[] = ['id','in',$retrun['rules']];
        }
        $res = Db::name('auth_rule')->field('name')->where($where)->select();
        foreach($res as $key=>$vo){
            if('#' != $vo['name']){
                $retrun['name'][] = $vo['name'];
            }
        }
        return $retrun;
    }

    public function getKeyVal($map = []){
        return $this->where('id','<>',1)->where($map)->where('status','1')->column('title','id');
    }
}