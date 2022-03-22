<?php

namespace app\admin\model;
use think\Model;

class MenuModel extends Model
{
    protected $name = 'auth_rule';
    
   // Enable automatic writing of timestamp fields
    protected $autoWriteTimestamp = true;


    /**
     * [getAllMenu to get all menus]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getAllMenu()
    {
        return $this->order('id asc')->select();
    }


    /**
     * [insertMenu add menu]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function insertMenu($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){            
                writelog(session('uid'),session('username'),'user['.session('username').'] failed to add menu',2);
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'user['.session('username').'] added menu successfully',1);
                return ['code' => 1, 'data' => '', 'msg' => 'Add menu successfully'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editMenu edit menu]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function editMenu($param)
    {
        try{
            $result = $this->update($param, ['id' => $param['id']]);
            if(false === $result){
                writelog(session('uid'), session('username'), 'User ['.session('username').'] Failed to edit menu', 2);
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'user['.session('username').']edit menu successful',1);
                return ['code' => 1, 'data' => '', 'msg' => 'Edit menu success'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneMenu gets a piece of information based on the menu id]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getOneMenu($id)
    {
        return $this->where('id', $id)->find();
    }



    /**
     * [delMenu delete menu]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function delMenu($id)
    {
        try{
            $this->where('id', $id)->delete();
            writelog(session('uid'), session('username'), 'User ['.session('username').'] Delete menu successfully', 1);
            return ['code' => 1, 'data' => '', 'msg' => 'Delete menu successfully'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}