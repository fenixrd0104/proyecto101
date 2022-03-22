<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class ConfigModel extends Model
{
    protected $name = 'config';

    // Enable automatic writing of timestamps
    protected $autoWriteTimestamp = true;

    /**
     * Get configuration list information based on conditions
     */
    public function getAllConfig($map, $nowpage, $limits)
    {
        return $this->where($map)->page($nowpage, $limits)->select();
    }

    /**
    * Get the number of all configuration information based on conditions
         * @param $map
         */
        public function getAllCount($map)
        {
            return $this->where($map)->count();
        }
    
        /**
         * insert information
         * @param $param
         */
        public function insertConfig($param)
        {
            try{
                $result = $this->save($param);
                if(false === $result){
                    return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
                }else{
                    return ['code' => 1, 'data' => '', 'msg' => 'Added successfully'];
                }
            }catch(PDOException $e){
                return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
        }
    
        /**
         * Edit information
         * @param $param
     */
    public function editConfig($param)
    {
        try{
            $result =  $this->where(['id' => $param['id']])->update($param);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
              return ['code' => 1, 'data' => '', 'msg' => 'edited successfully'];
            }
        }catch(PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    /**
     * Get configuration information based on id
     * @param $id
     */
    public function getOneConfig($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * delete configuration
     * @param $id
     */
    public function delConfig($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => 'successfully deleted'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}