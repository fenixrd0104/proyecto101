<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/28
 * Time: 13:37
 */

namespace app\common\command;

use app\common\model\MemberWalletLogModel;
use app\common\model\MemberWalletModel;
use app\common\model\Member;
use app\common\service\Users;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class Price extends Command
{
    protected function configure()
    {
        $this->setName('price')->setDescription('Currency prices');
    }


    protected function execute(Input $input, Output $output)
    {

        //daily task expired
        $timess = strtotime(date('Y-m-d'),time());
        Db::name('task_log')->where([['status','=',1],['task_type','=',1],['create_time','<',$timess] ])->update(['status'=>4]);

        // Challenge task expired
        Db::name('task_log')->where([['status','=',0],['task_type','=',2],['end_time','<',time() ]])->update(['status'=>4]);


        //Gift expired judgment
        $btime = time()-7*24*3600;
        $mianlist = Db::name('member')->field('id,mian_num,is_mian,mian_time,dl_level')->where([['mian_time','<',$btime],['mian_num','<',2],['dl_level','<=',0],['ty_level','>',0]])->select()->toArray();
        foreach ($mianlist as $values){

            $updates = [];
            if($values['dl_level']>0){
                $updates['is_mian'] = 0;
            }else {
                if ($values['mian_num'] >= 2) {
                    $updates['is_mian'] = 0;
                } else {

                    if($values['is_mian']>0){
                        if($values['mian_num']==1){
                            $updates['mian_num'] = 2;
                            $updates['is_mian'] = 0;
                        }else{
                            $updates['mian_num'] =  1;
                            $updates['mian_time'] = time();
                        }
                    }else{
                        if($values['mian_num']==1){
                            $updates['is_mian'] = 1;
                            $updates['mian_time'] = time();
                        }else{
                            $updates['mian_num'] = 2;
                            $updates['is_mian'] = 0;
                        }
                    }

                }
            }

            Db::name('member')->where(['id'=>$values['id']])->update($updates);

        }


        //The payment expires after 72 hours
        $dailist = Db::name('order')->where([['top_cate','=',2],['take_id','>',0],['pay_status','=', 0],['create_time','<',time()-259200],['order_status','=',0]])->select()->toArray();
        foreach ($dailist as $valuesd){
            $order_id = $valuesd['order_id'];
            $user_id = $valuesd['user_id'];
            $row = Db::name('order')->where(['order_id' => $order_id, 'user_id|take_id' => $user_id])->update(['order_status' => 3]);
            $data['order_id'] = $order_id;
            $data['action_user'] = $user_id;
            $data['action_note'] = 'The system canceled the order';
            $data['order_status'] = 3;
            $data['pay_status'] = $valuesd['pay_status'];
            $data['shipping_status'] = $valuesd['shipping_status'];
            $data['log_time'] = time();
            $data['status_desc'] = 'The system automatically cancels the order when the payment expires';
            Db::name('order_action')->insert($data);//Order operation record

            // subtract the sales of the item
            $order_goods = Db::name('order_goods')->where('order_id',$data['order_id'])->select();
            foreach ($order_goods as $k => $v) {
                Db::name('goods')->where('id',$v['goods_id'])->dec('sales_num', $v['goods_num'])->update();
                //increase inventory
                Db::name('goods')->where('id',$v['goods_id'])->inc('spec_num', $v['goods_num'])->update();
                Db::name('spec_goods')->where(['goods_id'=>$v['goods_id'],'spec_key'=>$v['spec_key']])->inc('spec_num')->update();
            }

        }


        $output->writeln('Judgment of overdue payment, overdue daily tasks and overdue gifts');

    }
}