<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use app\common\model\MemberWalletLogModel;
class Auction extends Command
{
    protected function configure()
    {
        $this->setName('Auction')->setDescription('auction');
    }


    protected function execute(Input $input, Output $output)
    {
        /* Auction item start/end auction executed every second */
        $map = [];
        $map[] = ['is_delete','=',0];
        $map[] = ['status','in','3,4'];
        $auction = Db::name('auction_goods')->where($map)->where([['start_time','<=',date('Ymd H:i:s',time())] ])->select();
        foreach ($auction as $k => $v) {
            //to be turned on
            if($v['status'] == 3){
                // Determine whether the auction is up to the start time
                if(strtotime($v['start_time']) <= time()){
                    Db::name('auction_goods')->where('id',$v['id'])->update(['status'=>4,'is_finish'=>3,'update_time'=>time ()]);
                    // $auction_msg = Db::name('auction_msg')->where(['auction_id'=>$v['id']])->select();
                    // $params['code'] = '';
                    // $params['content'] = 'Hello, '.$v['goods_name'].' has started the auction, please log in to participate. ';
                    // foreach ($auction_msg as $kk => $vv) {
                    //     if ($vv['mobile']) {
                    //         $info = \app\common\service\Msg::send_sms(1,$vv['country_code'].$vv['mobile'],$params);
                    //     }
                    // }
                }

            //to end
            }else if($v['status'] == 4){
                // whether to end time
                $time = strtotime($v['end_time']);
                if($time < time()){
                    // start things
                    // Db::startTrans();
                    // try {
                        Db::name('auction_goods')->where('id',$v['id'])->update(['status'=>1,'is_finish'=>2,'update_time'=>time ()]);
                        /* Someone bids the auction fee to the auctioneer */
                        // if($v['bidders']){
                        // $money = $v['price'] - $v['price']*$v['service_fee'] - $v['other_fee']; // deduct service fee and other fees
                        // $user_money = Db::name('member')->where('id',$v['uid'])->value('money');
                        // if($money > 0){
                        // Db::name('member')->where('id',$v['uid'])->inc('money',$money)->update();
                        // $MemberWalletLogModel = new MemberWalletLogModel();
                        // $MemberWalletLogModel->log($v['uid'],$money,$user_money,$user_money + $money,130,'Auction success',$v['id']);
                        // }
                        // }
                    // Db::commit();
                    // } catch (\Exception $e) {
                    // // rollback the transaction
                    //     Db::rollback();
                    // }
                }
           //over
            }else if($v['status'] == 1){
                // //No price increase, create a new secondary auction directly
                // if($v['pay_status'] !=1 && strtotime($v['end_time'] <= (time() - 3600)) && $v['auction_num'] == 1){
                //     $add = [
                //         'uid'           => $v['uid'],
                //         'goods_name'    => $v['goods_name'],
                //         'goods_img'     => $v['goods_img'],
                //         'goods_content' => $v['goods_content'],
                //         'auction_num'   => 2,
                //         'contact'       => $v['contact'],
                //         'valuation'     => $v['valuation'],
                //         'start_price'   => $v['start_price'],
                //         'start_time'    => date('Y-m-d H:i:s',time()),
                //         'end_time'      => date('Y-m-d H:i:s',time() + ($v['longtime'] * 60)),
                //         'longtime'      => $v['longtime'],
                //         'price'         => $v['start_price'],
                //         'price_range'   => $v['price_range'],
                //         'is_private'    => $v['is_private'],
                //         'password'      => $v['password'],
                //         'other_fee'     => $v['other_fee'],
                //         'other_mark'    => $v['other_mark'],
                //         'service_fee'   => $v['service_fee'],
                //         'status'        => 4,
                //         'is_finish'     => 3,
                //         'is_push'       => $v['is_push'],
                //         'remark'        => $v['remark'],
                //         'create_time'   => time(),
                //         'update_time'   => time()
                //     ];
                //     Db::name('auction_goods')->where('id',$v['id'])->update(['status'=>7,'update_time'=>time()]);
                //     Db::name('auction_goods')->insert($add);
                // }
            }
        }
        $output->writeln('Done | Auction ok!!!!');
    }

}