<?php

namespace app\common\model;
use think\Model;
use think\facade\Db;

class MoneyLog extends Model
{
    // Enable automatic writing of timestamp fields
    protected $autoWriteTimestamp = true;

    /**
     * Money records
     * @param $uid User ID
     * @param $money amount of money
     * @param int $act Action 1 Recharge by yourself 2 Recharge by administrator 3 Payment 4 Refund 21 Deductions from store purchases 22 Refunds for cancelled orders in stores 23 Refunds for returned products in stores
     * @param int $status recharge status 0 waiting for payment 1 payment successful
     * @param string $remark remark
     * @param int $executor executor 0 self
     */
    public static function operate($uid,$money,$act=1,$status=0,$remark='',$executor=0){
        $create_order_no='cz'.time() . rand(1000, 9999);
        $data=[
            'out_trade_no'=>$create_order_no,
            'uid'=>$uid,
            'money'=>$money,
            'status'=>$status,
            'executor'=>$executor,
            'act'=>$act,
            'remark'=>$remark,
        ];

        if(self::create($data)){
            return $data;
        }else{
            return '';
        }
    }

    /**
     * increase money
     * @param $uid
     * @param $money
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function setInc($uid, $money)
    {
        if (Db::name('member')->where(['id'=>$uid])->inc('money', $money)->update())  {
            return ['code'=>1,'msg'=>'Successful operation'];
        } else {
            return ['code'=>0,'msg'=>Db::name('member')->getLastSql()];
        }
    }

    /**
     * reduce money
     * @param $uid
     * @param $money
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function setDec($uid, $money)
    {

        if (Db::name('member')->where(['id'=>$uid])->dec('money', $money)->update())  {
            return ['code'=>1,'msg'=>'Successful operation'];
        } else {
            return ['code'=>0,'msg'=>Db::name('member')->getLastSql()];
        }
    }



}

