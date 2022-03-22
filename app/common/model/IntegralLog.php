<?php

namespace app\common\model;
use think\facade\Db;
use think\Model;

class IntegralLog extends Model
{
    // Enable automatic writing of timestamp fields
        protected $autoWriteTimestamp = true;
    
        /**
         * Points record
         * @param $uid User ID
         * @param $money amount of money
         * @param int $act Action 1self 2administrator charge 3payment 4refund 10 shopping rebate points 11referring users to bind the card 21store purchase product deduction 22store canceled order refund 23store return product refund
         * @param int $status status 0?1 success
         * @param string $remark remark
         * @param int $executor executor 0 self
     */
    public static function operate($uid,$num,$act=1,$status=1,$remark='',$executor=0){
        $data=[
            'uid'=>$uid,
            'num'=>$num,
            'status'=>$status,
            'executor'=>$executor,
            'act'=>$act,
            'remark'=>$remark,
        ];

        if(self::create($data)){
            return $data;
        }
    }

    /**
     * add points
     * @param $uid
     * @param $integral
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function setInc($uid, $integral)
    {
        if (Db::name('member')->where(['id'=>$uid])->inc('integral', $integral)->update())  {
            return ['code'=>1,'msg'=>'Successful operation'];
        } else {
            return ['code'=>0,'msg'=>Db::name('member')->getLastSql()];
        }
    }

    /**
     * reduce points
     * @param $uid
     * @param $integral
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function setDec($uid, $integral)
    {

        if (Db::name('member')->where(['id'=>$uid])->dec('integral', $integral)->update())  {
            return ['code'=>1,'msg'=>'Successful operation'];
        } else {
            return ['code'=>0,'msg'=>Db::name('member')->getLastSql()];
        }
    }







}

