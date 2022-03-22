<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class MemberGroupModel extends Model
{
    protected $name = 'member_group';   
    protected $autoWriteTimestamp = true;   // Enable automatic writing of timestamps

    /**
     * Get all data according to conditions
     */
    public function getAll($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage,$limits)->order('id asc')->select();     
    }


    /**
     * Get all quantities based on condition
     */
    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }

    /**
     * Get all member group information
     */ 
    public function getGroup()
    {
        return $this->select();
    }


    /**
     * Insert information
     */
    public function insertGroup($param)
    {
        try{
            $result =  $this->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'Added successfully'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Edit information
     */
    public function editGroup($param)
    {
        try{
            $result =  $this->where(['id' => $param['id']])->update($param);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'Edited successfully'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Get a message by id
     */
    public function getOne($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * delete message
     */
    public function delGroup($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => 'successfully deleted'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}