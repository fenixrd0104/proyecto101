<?php
namespace app\common\service;

use app\admin\model\MemberModel;
use app\admin\model\ShopOrderModel;
use app\common\model\IntegralLog;
use app\common\model\Member;
use app\common\model\MoneyLog;
use app\common\model\Order;
use app\common\model\TianyuanLog;
use think\facade\Db;

class Settlement{

    //configure
    public $config;
    public $djlv=3;

    /**
     * Settlement constructor.
     * @param $config
    [nfm_recommended_members] => 20
    [nfm_register_member] => 10
    [nfm_consumer_member] => 60
    [nfm_maker_member] => 300
    [nfm_partner_member] => 1000
    [nfm_founder_member] => 2000
    [nfm_consumer_integral] => 20
    [nfm_maker_integral] => 24
    [nfm_partner_integral] => 27
    [nfm_founder_integral] => 30
    [nfm_shop_integral] => 20
    [nfm_introduce_integral] => 1
    [nfm_fund_integral] => 4
    [nfm_maintain_integral] => 1
    [nfm_consumer_upgrade] => 200
    [nfm_maker1_upgrade] => 15
    [nfm_maker2_upgrade] => 6
    [nfm_partner_upgrade] => 15
    [nfm_founder_bonus] => 1
    [nfm_partner_bonus] => 4
    [nfm_precipitate_uid] => 1
     */
    public function __construct($config)
    {
        $this->config=$config;
    }
    //Operation after user registration $uid,$num,$type,$remark,$executor=0,$relation_id=0
    public function reg($uid,$referid){
        if($this->config['nfm_register_member'] > 0){
            //Add beans to the user and do not freeze
             TianyuanLog::tianyuanInc($uid,$this->config['nfm_register_member'],1,'New user registration reward');
        }
        if($referid && $this->config['nfm_recommended_members'] > 0){
            $member_model = new MemberModel();
            $member_model->where(['id'=>$referid])->inc('tnum')->update();
            //Add beans to recommenders
            TianyuanLog::tianyuanInc($referid,$this->config['nfm_recommended_members'],2,'Referral User Registration Reward');
        }
    }

   //User upgrade
    public function huiyuan_shengji($uid,$jibie,$is_bangka=0){
        $member_model = new MemberModel();
        $user_info = $member_model->find($uid);
        // Determine the user's current level
        $curr_group_id = $user_info['group_id'];
        if($curr_group_id == $jibie && !$is_bangka){
            return false;
        }
        if($curr_group_id > $jibie && !$is_bangka){
            return false;
        }
        if($jibie > 5 && !$is_bangka ){
            return false;
        }

        if($jibie == 2 || $is_bangka){
            //Upgrade send beans
            $curr_group_id < 2 && $up['group_id']=2;

            if($is_bangka){
                $up['is_bangka']=1;
                //Add money to the card-bound user.
                //TODO:: INC requires setInc in 5.1
                if($member_model->where(['id'=>$uid])->inc('money',1000)->update()){
                    MoneyLog::operate($uid,1000,1,1,'User Card Rewards');
                }
            }
            $member_model->where(['id'=>$user_info['id']])->update($up);

            if($this->config['nfm_consumer_member'] && $curr_group_id<2 ){
                $a2 = round($this->config['nfm_consumer_member']/$this->djlv);
                $b2 = $this->config['nfm_consumer_member']-$a2;
                TianyuanLog::tianyuanInc($user_info['id'],$a2,3,'Upgrade consumption member reward');
                TianyuanLog::tianjiaDongjie($user_info['id'],$b2,4,'Upgrade consumption membership freeze');
            }
            //If there is a superior, judge whether the superior needs to be upgraded
            $referid=$user_info['referid'];
            if(!$referid){
                return true;
            }
            //If it is tied to the card, it will add money
            if($is_bangka && $this->config['nfm_card_reward'] ){
                //$uid,$integral,$act,$remark,$relation_id=0
                $this->IncreaseKf($referid,$this->config['nfm_card_reward'],11,'Recommended user card binding reward',$user_info['id']);

            }
            // Determine the status of the superior now
            $user_info = $member_model->find($referid);
            //If the superior is also a consumer member, judge whether it is time to upgrade
            if($user_info['group_id'] != 2){
                return true;
            }
            if($is_bangka){
               $count =  $member_model->where(['referid'=>$user_info['id'],'is_bangka'=>1])->count();
               $group_count =  $member_model->where([['referid','=',$user_info['id']],['group_id','>=',2]])->count();
               if($count >= $this->config['nfm_maker2_upgrade'] || $group_count >= $this->config['nfm_maker1_upgrade']){
                   $jibie=3;
               }else{
                   return true;
               }
            }else{
                $count =  $member_model->where([['referid','=',$user_info['id']],['group_id','>=',2]])->count();
                if($count >= $this->config['nfm_maker1_upgrade']){
                    $jibie=3;
                }else{
                    return true;
                }
            }

        }
        if($jibie == 3){
            $member_model->where(['id'=>$user_info['id']])->update(['group_id'=>3]);
            if($this->config['nfm_maker_member']){
                $a3 = round($this->config['nfm_maker_member']/$this->djlv);
                $b3 = $this->config['nfm_maker_member']-$a3;
                TianyuanLog::tianyuanInc($user_info['id'],$a3,5,'Upgrade Maker Membership Rewards');
                TianyuanLog::tianjiaDongjie($user_info['id'],$b3,6,'Upgrade Maker Member Freeze');
            }
            //If there is a superior, judge whether the superior needs to be upgraded
            $referid=$user_info['referid'];
            if(!$referid){
                return true;
            }
            // Determine the status of the superior now
            $user_info = $member_model->find($referid);
            //If the superior is also a consumer member, judge whether it is time to upgrade
            if($user_info['group_id'] != 3){
                return true;
            }
            //Determine whether to upgrade the recommender
            $count =  $member_model->where(['referid'=>$user_info['id'],'group_id'=>3])->count();
            if($count >= $this->config['nfm_partner_upgrade']){
                $jibie=4;
            }else{
                return true;
            }
        }
        if($jibie == 4){
            $member_model->where(['id'=>$user_info['id']])->update(['group_id'=>4]);
            if($this->config['nfm_partner_member']){
                $a4 = round($this->config['nfm_partner_member']/$this->djlv);
                $b4 = $this->config['nfm_partner_member']-$a4;
                TianyuanLog::tianyuanInc($user_info['id'],$a4,7,'Upgrade partner reward');
                TianyuanLog::tianjiaDongjie($user_info['id'],$b4,8,'Upgrade partner freeze');
            }
            return true;
        }
        if($jibie == 5){
            $member_model->where(['id'=>$user_info['id']])->update(['group_id'=>5]);
            if($this->config['nfm_founder_member']){
                $a4 = round($this->config['nfm_founder_member']/$this->djlv);
                $b4 = $this->config['nfm_founder_member']-$a4;
                TianyuanLog::tianyuanInc($user_info['id'],$a4,9,'Upgrade co-founder reward');
                TianyuanLog::tianjiaDongjie($user_info['id'],$b4,10,'Upgrade co-founder freeze');
            }
            return true;
        }
    }

    //return points by level
    public function fanjiefen($user_info,$order_id,$order_amount){

        $user_model = new MemberModel();
        $nfm_consumer_integral = $this->config['nfm_consumer_integral'];
        $nfm_maker_integral = $this->config['nfm_maker_integral'];
        $nfm_partner_integral = $this->config['nfm_partner_integral'];
        $nfm_founder_integral = $this->config['nfm_founder_integral'];
        // expected
        $yuji=$order_amount*($nfm_consumer_integral+$nfm_maker_integral+$nfm_partner_integral+$nfm_founder_integral)*0.01;
        //actual
        $djarr = array(1=>0,2=>$nfm_consumer_integral,3=>$nfm_consumer_integral+$nfm_maker_integral,4=>$nfm_consumer_integral+$nfm_maker_integral+$nfm_partner_integral,5=>$nfm_consumer_integral+$nfm_maker_integral+$nfm_part
        //hint
        //
        $shiji=0;
        $dengji = 1;
        $buy_info = $user_info;
        for ($i=0;$i<1000;$i++){
            $mdj = $buy_info['group_id'];
            if($mdj>$dengji){
                $mdj=$mdj>5?5:$mdj;
                $jlbi = $djarr[$mdj]-$djarr[$dengji];
                $buy_kf1= $jlbi*$order_amount*0.01;
                $shiji+=$buy_kf1;
                $this->IncreaseKf($buy_info['id'],$buy_kf1,10,'Shopping rebates',$order_id);
                $dengji = $mdj;
            }
            if($dengji>=5 || !$buy_info['referid']){

                return $dengji>=5?0:$yuji-$shiji;
            }else{
                $buy_info = $user_model->find($buy_info['referid']);
            }

        }

    }

    private function IncreaseKf($uid,$integral,$act,$remark,$relation_id=0){
        //TODO:: This cannot be directly overwritten, and both sides must be modified at the same time
          $member_model = new MemberModel();
        if($member_model->where(['id'=>$uid])->inc('integral', $integral)->update()){
            //Add log $uid,$num,$act=1,$status=1,$remark='',$executor=0
            IntegralLog::operate($uid,$integral,$act,1,$remark,0,$relation_id);
        }
    }

    //The operation consumed by the user
    public function xiaofei($uid,$order_id,$mendian= false){
        // Check the order data first
        if($mendian){
            $order_model = new ShopOrderModel();
        }else{
            $order_model = new Order();
        }
        $order_info =$order_model->find($order_id);
        //Determine the membership card application time
        $create_time = Db::name('money_log')->where(['uid'=>$uid,'remark'=>'User Card Rewards'])->value('create_time');
        $user_money = $order_info['user_money'];
        if($create_time && $user_money){
            //Query his consumption after applying for the card
            $true_user_money1 = Db::name('shop_order')->where(['user_id'=>$uid,'pay_status'=>1])->where('pay_time','>',$create_time)->sum('user_money');
            $true_user_money2 = Db::name('order')->where(['user_id'=>$uid,'pay_status'=>1])->where('pay_time','>',$create_time)->sum('user_money');
            $true_user_money=$true_user_money1+$true_user_money2;
           $user_money=$true_user_money-1000>=$order_info['user_money']?$order_info['user_money']:($true_user_money-1000>0?$true_user_money-1000:0);
        }
        $amount=$order_info['integral_money']+$user_money+$order_info['order_amount'];
        //Viewing user information
        $user_model = new MemberModel();
        $user_info =$user_model->find($uid);

        //Points back
        //$num = $this->fanjiefen($user_info,$order_id,$amount);
        // if($num && $this->config['nfm_precipitate_uid']){
        //     //put in precipitation users
        //     $this->IncreaseKf($this->config['nfm_precipitate_uid'],$num,20,'Accumulate shopping rebate points',$order_id);
        // }


        // 		$shop_info = Db::name('shop_lists')->where(['id'=>$order_info['shop_id']])->find();
        // 		if($shop_info){
        //     //shop service fee [nfm_shop_integral] => 20

        // 			$nfm_shop_integral = $amount*$this->config['nfm_shop_integral']*0.01;
        // 			if($this->config['nfm_store_type'] == $shop_info['type']){
        // 			$this->IncreaseKf($shop_info['uid'],$nfm_shop_integral,12,'shop service fee',$order_id);
        // 			}else{
        // 			//Put the precipitation user
        // 			$this->IncreaseKf($this->config['nfm_precipitate_uid'],$nfm_shop_integral,21,'precipitate shop service fee',$order_id);
        // }

        // 			// Introduce subsidiary success award [nfm_introduce_integral] => 1
        // 			$nfm_introduce_integral=$amount*$this->config['nfm_introduce_integral']*0.01;
        // 			if($this->config['nfm_store_type'] == $shop_info['type'] && $shop_info['referid']){
        // 			$this->IncreaseKf($shop_info['referid'],$nfm_introduce_integral,13,'Join store introduction reward',$order_id);
        // 			}else{
        // 			//Put the precipitation user
        //         $this->IncreaseKf($this->config['nfm_precipitate_uid'],$nfm_introduce_integral,22,'沉淀加盟店介绍奖励',$order_id);
        //     }
        // }
       // //Bean Fund 4% [nfm_fund_integral] => 4
        // $nfm_fund_integral =$amount*$this->config['nfm_fund_integral']*0.01;
        // if($nfm_fund_integral){
        // $this->IncreaseKf($this->config['nfm_fund_uid'],$nfm_fund_integral,23,'Precipitated bean fund reward',$order_id);
        // }
        // //Operation and maintenance 1%. [nfm_maintain_integral] => 1
        // $nfm_maintain_integral =$amount*$this->config['nfm_maintain_integral']*0.01;
        // if($nfm_maintain_integral){
        // $this->IncreaseKf($this->config['nfm_maintain_uid'],$nfm_maintain_integral,24,'Precipitation O&M Rewards',$order_id);
        // }

        // //Determine that the user is a registered member and the current amount is greater than the set consumption amount
        // if($user_info['group_id'] == 1 && ($user_info['xprice'] + $amount) > $this->config['nfm_consumer_upgrade'] ){
        // // Upgrade by yourself
        //     $this->huiyuan_shengji($user_info,2);
        // }

    }
    // Add Team Total Amount
    public function jia_tprice($uid,$order_amount){
      $MemberModel =   new MemberModel();
      $current_group=3;
      for ($i=0;$i<100;$i++){
         $info =  $MemberModel->where(['id'=>$uid])->find();
         if(!$info){
             break;
         }
         if($info['group_id'] > $current_group){
             $MemberModel->where(['id'=>$uid])->update(['tprice'=>$info['tprice']+$order_amount]);
         }
         //Add the consumption amount to the user
                  if($info['group_id'] >= 5 || $info['referid'] == 0) {
                      break;
                  }
                 $uid =$info['referid'];
               }
             }
             //The operation of user binding card
             public function bangka($uid){
                 $this->huiyuan_shengji($uid,2,true);
             }
             //The operation after the manual upgrade in the background
    public function shengji($uid,$jibie){
        $this->huiyuan_shengji($uid,$jibie);
    }



}

