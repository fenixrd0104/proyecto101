<?php

namespace app\common\model;
use think\Model;

class MemberWalletLogModel extends Model
{
    protected $name = 'member_wallet_log';
    protected $autoWriteTimestamp = true; // Enable automatic write timestamp

    /**
     * @param $uid User ID
     * @param $currency currency type ID
     * @param $number number
     * @param $ymoney original quantity
     * @param $nmoney new quantity
     * @param $type type
     * 1-20 KRC 1 Deposit 2 Withdrawal Application 3 Convert usdt 4 Usdt Convert KRC 5 Withdrawal Rejection 6 Withdrawal Through 7 Dynamic Rewards of Agents 8 Rewards of Agents 9 Dynamic Rewards of Node Payment 12Payment Rewards13Dynamic Rewards for Payment14Node Package Rewards15Purchase Payment Coupon16Order Payment17Order Receipt18Payment Order19Return Payment
     * 21-40 USDT 21 Deposit 22 Withdrawal application 23 Convert to KRC 24 KRC convert to usdt 25 Withdrawal refused 26 Withdrawal through 27 Transfer to reduce KRC 28 Transfer to increase KRC
     * 41-60 Gift Coins 41 Recharge 42 Withdrawal Application 43 Withdrawal Rejection 44 Withdrawal Pass 51 Rewards for Payment on behalf of 52 Rewards for Dynamic Coins for Payment on behalf of 53 Rewards for Dynamic Coins for Node Payments 207 Rewards for Tokens for Experience 209 Rewards for Tokens for Experience Payment
     * 61-80 Coupon 61 Buy VIP products for free 62 Pay with Coupon
     * 81-100 coupons 81 order payment coupons reduced 82 order purchases get coupons
     * 101-120 Original shares 101 Free original shares for VIP purchases 102 Original shares reduced
     * 130-149 Auction KRC 130 Auction success 131 Auction deposit deduction 132 Auction deposit and reward 133 Auction supplementary fee 134 Auction reward 135 Auction team reward 136 Auction red envelope reward 137 Direct purchase red envelope reward 138 Payment red envelope reward 139 Referral red envelope reward
     * 200 Other operation KRC 200 Donation 201 Challenge mission reward 202 Open experience member 203 Direct push experience member reward 204 Experience team reward 205 Experience team level reward 206 Experience payment reward 208 Experience payment dynamic reward
     *1000 free gift on behalf of the payment
     * @param $remarks remarks
     * @param int $act corresponds to the operation-related ID
     * @return bool
     */
    static public function log($uid,$number,$ymoney,$nmoney,$type,$z_remarks,$act=0){
//        $time=0?$time=time():$time=$time;
       return self::insert([
           'uid'=>$uid,
           'type'=>$type,
           'number'=>$number,
           'ymoney'=>$ymoney,
           'nmoney'=>$nmoney,
           'z_remarks'=>$z_remarks,
           'act'=>$act,
//           'create_time'=>$time,
           'create_time'=>time(),
           'update_time'=>time(),
       ]) ;
    }

    //User wallet flow record
    public function getMemberWalletLogByWhere($nowpage, $limits,$map,$order)
    {
        $res['lists']=$this->alias('w')
            ->join('member m','w.uid =m.id','LEFT')
            ->where($map)
            ->field('w.*,m.account,m.mobile')
            ->order($order)
            ->page($nowpage,$limits)
            ->select();
        //Calculate total pages
        $res['count']=$this->getMemberWalletLogByWhereCount($map);
        return $res;
    }
    public function getMemberWalletLogByWhereCount($map){
        return $this->alias('w')
            ->join('member m','w.uid =m.id','LEFT')
            ->where($map)
            ->count();
    }

}
