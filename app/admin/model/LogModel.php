<?php

namespace app\admin\model;
use think\Model;

class LogModel extends Model
{
    protected $name = 'log';

    /**
     * delete log
     */
    public function delLog($log_id)
    {
        try{
            $this->where('log_id', $log_id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => 'Delete log successfully'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}