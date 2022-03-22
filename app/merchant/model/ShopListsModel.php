<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class ShopListsModel extends Model
{

    protected $name = 'shop_lists';
    protected $autoWriteTimestamp = true;
    public function forUser()
    {
        return $this->hasOne('UserModel', 'id', 'uid');
    }

    /**
     * Get user list information based on search criteria
     */
    public function getListByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)
            ->withAttr('uid', function($value, $data) {
                return Db::name('member')->where('id',$value)->value('nickname');
            })->page($Nowpage, $limits)
            ->order('id desc')
            ->select();
    }

    /**
     * Get the number of all users based on search criteria
          * @param $where
          */
         public function getAllCount($where)
         {
             return $this->where($where)->count();
         }
     
         /**
          * insert branch
          * @param $param
          */
         public function insertShop($param)
         {
             try{
                 $result = $this->save($param);
                 if(false === $result){
                     return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
                 }else{
                     writelog(session('uid'),session('username'),'branch ['.$param['name'].'] added successfully',1);
                     return ['code' => 1, 'data' => '', 'msg' => 'Add branch successfully'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    public function editSubshop($param,$map)
    {
        try{
            $result =  $this->update($param, $map);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
               writelog(session('uid'), session('username'), 'Branch ['.$param['name'].'] Edited successfully', 1);
                return ['code' => 1, 'data' => '', 'msg' => 'Edit branch successfully'];
            }
        }catch(PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    public function getOneSubshop($id)
    {
        return $this->where('id', $id)->find();
    }

    public function delSubShop($id)
    {
        try{

            $this->where('id', $id)->delete();

            writelog(session('uid'),session('username'),'user['.session('username').'] delete branch successfully(ID='.$id.')',1);
            return ['code' => 1, 'data' => '', 'msg' => 'Delete branch successfully'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    public function getKeyVal($map){
        return $this->where($map)->column('name','id');
    }



}