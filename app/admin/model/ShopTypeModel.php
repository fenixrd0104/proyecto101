<?php

namespace app\admin\model;
use think\Model;

class ShopTypeModel extends Model
{
    protected $name = 'shop_type';
    
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function getAllMenu()
    {
        return $this->order('id asc')->select();       
    }



    public function insertOne($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){            
                writelog(session('uid'),session('username'),'用户【'.session('username').'】添加店铺类型失败',2);
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'用户【'.session('username').'】添加店铺类型成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '添加店铺类型成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    public function editOne($param)
    {
        try{
            $result =  $this->update($param, ['id' => $param['id']]);
            if(false === $result){
                writelog(session('uid'),session('username'),'用户【'.session('username').'】编辑店铺类型失败',2);
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'用户【'.session('username').'】编辑店铺类型成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '编辑店铺类型成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    public function getOne($id)
    {
        return $this->where('id', $id)->find();
    }



    public function delOne($id)
    {
        try{
            $this->where('id', $id)->delete();
            writelog(session('uid'),session('username'),'用户【'.session('username').'】删除店铺类型成功',1);
            return ['code' => 1, 'data' => '', 'msg' => '删除店铺类型成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    public function getKeyVal(){
        return $this->order('id desc')->column('name','id');
    }

}