<?php

namespace app\merchant\model;
use think\Model;

class ShopTypeModel extends Model
{
    protected $name = 'shop_type';
    
    // Enable automatic writing of timestamp fields
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
                    writelog(session('uid'),session('username'),'user['.session('username').'] failed to add store type',2);
                    return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
                }else{
                    writelog(session('uid'), session('username'), 'User ['.session('username').'] Add store type successfully', 1);
                    return ['code' => 1, 'data' => '', 'msg' => 'Add store type successfully'];
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
                writelog(session('uid'),session('username'),'User ['.session('username').'] Failed to edit store type',2);
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'User ['.session('username').'] Editing the store type successfully',1);
                return ['code' => 1, 'data' => '', 'msg' => 'Edit shop type successfully'];
            }
        }catch(PDOException $e){
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
            writelog(session('uid'),session('username'),'User ['.session('username').'] Delete store type successfully',1);
            return ['code' => 1, 'data' => '', 'msg' => 'Delete the store type successfully'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    public function getKeyVal(){
        return $this->order('id desc')->column('name','id');
    }

}