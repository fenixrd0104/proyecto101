<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class AdPositionModel extends Model
{
    protected $name = 'ad_position';

    // Enable automatic writing of timestamps
    protected $autoWriteTimestamp = true;

    /**
     * [getAll gets all data according to conditions]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getAll($nowpage, $limits)
    {
        return $this->page($nowpage, $limits)->order('id asc')->select();
    }

    /**
     * insert information
     * @param $param
     */
    public function insertAdPosition($param)
    {
        try{
            $result = $this->validate('AdPositionValidate')->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'Add ad slot successfully'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Edit information
     * @param $param
     */
    public function editAdPosition($param)
    {
        try{
            $result = $this->validate('AdPositionValidate')->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'Edit ad slot successfully'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Get a message by id
     * @param $id
     */
    public function getOne($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * [getAll to get all ad slots]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getAllPosition()
    {
        return $this->order('id asc')->select();
    }


    /**
     * delete message
     * @param $id
     */
    public function delAdPosition($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => 'Delete ad slot successfully'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}