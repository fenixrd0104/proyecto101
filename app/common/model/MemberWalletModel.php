<?php

namespace app\common\model;
use think\Db;
use think\Model;
use think\Exception;

class MemberWalletModel extends Model
{
    protected $name = 'member_wallet';
    protected $autoWriteTimestamp = true; // Enable automatic write timestamp

    public function getOneUserWalletLists($uid){
       return $this->where(['uid'=>$uid])->select();
    }
    //d multi-condition search
    public function getMemberWalletByWhere($map,$Nowpage, $limits,$order){
        $res['list'] = $this->alias('mw')
            ->join('member m','mw.uid = m.id')
            ->join('currency c','mw.currency = c.id')
            ->where($map)->field('mw.*,m.account,c.name,m.realname,m.mobile')
            ->order($order)
            ->page($Nowpage,$limits)
            ->select();
        $res['count']=$this->alias('mw')
            ->join('member m','mw.uid = m.id')
            ->join('currency c','mw.currency = c.id')
            ->where($map)
            ->count();
        return $res;
    }

    /**
     * Get a wallet of a user without automatic creation
     * @param $uid User ID
     * @param $currency_id currency ID
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneUserWallet($uid,$currency_id){
        $info = $this->where(['uid'=>$uid,'currency'=>$currency_id])->find();
        if(!$info){
            $data = ['uid'=>$uid,'currency'=>$currency_id,'balance'=>0,'lock_balance'=>0,'create_time'=>time(),'update_time'=>time()];
            self::insert($data);
            $info = $this->where(['uid'=>$uid,'currency'=>$currency_id])->find();
        }
        return $info;
    }


    /**
    * Freeze user balance "Replace available balance to frozen balance"
     * @param $uid User ID
     * @param $currency_id currency ID
     * @param $num number
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function dongjie($uid,$num)
    {
// $info = self::where(['uid'=>$uid,'currency'=>$currency_id])->find();
         $info = Db::name('member')->where(['uid'=>$uid])->find();
         if($info){
            if($info->money < $num){
                throw new Exception('Insufficient available balance', 10001);
            }
            $info->money=$info->money-$num;
            $info->lock_money=$info->lock_money+$num;
            if($info->save()){
                return true;
            }else{
              throw new Exception('Frozen failed', 10002);
            }
         }else{
             throw new Exception('The wallet was not found', 10000);
         }

    }

    /**
     * Freeze "Replace available frozen balance to available balance"
     * @param $uid
     * @param $currency_id
     * @param $num
     * @return bool
     * @throws Exception
     * @throws \think\exception\DbException
     */
    static public  function shifang_dongjie($uid,$currency_id,$num)
    {
        $info = self::where(['uid'=>$uid,'currency'=>$currency_id])->find();
        if($info){
            if($info->lock_balance < $num){
              throw new Exception('Insufficient frozen balance', 10001);
            }
            $info->balance=$info->balance+$num;
            $info->lock_balance=$info->lock_balance-$num;
            if($info->save()){
                return true;
            }else{
                throw new Exception('Failed to release freeze', 10002);
            }
        }else{
            throw new Exception('The wallet was not found', 10000);
        }

    }

    /**
     * Add Freeze "Use to add Freeze"
     * @param $uid
     * @param $currency_id
     * @param $num
     * @return bool
     * @throws Exception
     */
    static public function zengjia_dongjie($uid,$currency_id,$num){
        $info = self::where(['uid'=>$uid,'currency'=>$currency_id])->find();
        if($info){
            $info->lock_balance=$info->lock_balance+$num;
            if($info->save()){
                return true;
            }else{
             throw new Exception('Failure to add freeze', 10002);
            }
        }else{
            throw new Exception('The wallet was not found', 10000);
        }
    }

    /**
     * Deduction Freeze "Used to Reduce Freeze"
     * @param $uid
     * @param $currency_id
     * @param $num
     * @return bool
     * @throws Exception
     */
    static public function kouchu_dongjie($uid,$currency_id,$num){
        $info = self::where(['uid'=>$uid,'currency'=>$currency_id])->find();
        if($info){
            if($info->lock_balance < $num){
              throw new Exception('Insufficient frozen balance', 10001);
            }
            $info->lock_balance=$info->lock_balance-$num;
            if($info->save()){
                return true;
            }else{
                throw new Exception('Failure to deduct freeze', 10002);
            }
        }else{
            throw new Exception('The wallet was not found', 10000);
        }
    }

    /**
     * Increase available balance "Add available balance directly to an account"
     * @param $uid
     * @param $currency_id
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     */
    static  public function zengjiakeyong($uid,$currency_id,$num){
        $info = self::where(['uid'=>$uid,'currency'=>$currency_id])->find();
        if(!$info){
            $data = [
                'uid'=>$uid,
                'currency'=>$currency_id,
                'balance'=>$num,
                'lock_balance'=>0,
                'create_time'=>time(),
                'update_time'=>time(),
            ];
            return self::insert($data);
        }else{
            $info->balance =$info->balance+$num;
            return $info->save();
        }
    }

    /**
     * Debit user balance "Direct deduction from available balance"
     * @param $uid User ID
     * @param $currency_id currency ID
     * @param $num number
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function kouchu($uid,$currency_id,$num)
    {
        $info = self::where(['uid'=>$uid,'currency'=>$currency_id])->find();
        if($info){
            if($info->balance < $num){
                throw new Exception('Insufficient available coins', 10001);
            }
            $info->balance=$info->balance-$num;
            if($info->save()){
                return true;
            }else{
                throw new Exception('Failed to deduct coins', 10002);
            }
        }else{
            throw new Exception('The wallet was not found', 10000);
        }

    }






}
