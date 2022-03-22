<?php

namespace app\admin\model;
use think\facade\Db;
use think\Model;

class UserType extends Model
{
    protected  $name = 'auth_group';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * [getRoleByWhere 根据条件获取角色列表信息]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getRoleByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }



    /**
     * [getRoleByWhere 根据条件获取所有的角色数量]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getAllRole($where)
    {
        return $this->where($where)->count();
    }



    /**
     * [insertRole 插入角色信息]
     * @author [田建龙] [864491238@qq.com]
     */    
    public function insertRole($param)
    {
        try{
            $result =  $this->save($param);
            if(false === $result){               
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加角色成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editRole 编辑角色信息]
     * @author [田建龙] [864491238@qq.com]
     */  
    public function editRole($map,$param)
    {
        try{
            $result =  $this->where($map)->save($param);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑角色成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneRole 根据角色id获取角色信息]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function getOneRole($id)
    {
        return $this->where('id', $id)->find();
    }



    /**
     * [delRole 删除角色]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function delRole($map)
    {
        try{
            $this->where($map)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除角色成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getRole 获取所有的角色信息]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function getRole()
    {
        return $this->where('id','<>',1)->select();
    }


    /**
     * [getRole 获取角色的权限节点]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function getRuleById($id)
    {
        $res = $this->field('rules')->where('id', $id)->find();
        return $res['rules'];
    }


    /**
     * [editAccess 分配权限]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function editAccess($map,$param)
    {
        try{
            $this->where($map)->save($param);
            return ['code' => 1, 'data' => '', 'msg' => '分配权限成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }




    /**
     * [getRoleInfo 获取角色信息]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function getRoleInfo($uid){
        //先获取 用户的所有组
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