<?php
namespace app\common\command;

use app\common\model\Member;
use app\common\model\MemberWalletLogModel;
use app\common\model\ManageMoneyListModel;
use app\common\model\MemberWalletModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class Licais extends Command
{
    protected function configure()
    {
        $this->setName('licais')->setDescription('Financial products income success');
    }


    protected function execute(Input $input, Output $output)
    {
        /*------------------------------Financial maturity--------------------------------------*/
        $times = time();
        //Determine whether wealth management income has been issued on the day
        $licaiinfo = Db::name('member_wallet_log')->where(['type' => 10])->order(['id desc'])->find();
        if ($licaiinfo) {
            if ($licaiinfo['create_time'] >= $times) {
                $output->writeln('0|wulicai ok!!!!');
            }
        }
        //open transaction
        $ManageMoneyListModel = new ManageMoneyListModel();
        $MemberWalletModel = new MemberWalletModel();
        $comeBack = $ManageMoneyListModel
            ->where('status', '<>', 2)
            ->select();
        foreach ($comeBack as $key => $value) {
//             start transaction
            Db::startTrans();
            try {
                if ($value['status'] == 0) {
// 				//The state is equal to 0 and the time is between the start and end of the income, then change the state to income
                    if ($times >= $value['income_time']) {
                        Db::name('manage_money_list')->where(array('id' => $value['id'], 'cid' => $value['cid']))->save(array('status ' => 1));
                    }
                } elseif ($value['status'] == 1) {
                    //while earning
                    //See if the current user has a wallet
                    for ($i = 1; $i < $value['licai_day']+1; $i++) {
                        $timejia = $value['created_at'] + 60 * 60 * 24 * $i;
                        $timeend = $value['created_at'] + 60 * 60 * 24 * ($i+1);
                        $havs = Db::name('member_wallet_log')
                            ->where([
                                ['type', '=', '10'],
                                ['create_time', '>=', $timejia],
                                ['create_time', '<', $timeend],
                                ['act', '=', $value['id']],
                            ])->count();
                        if($havs==0){
                            if($i!=$value['licai_day']){
////                        Current time early morning start time
// 								$today_start = strtotime(date('Y-m-d', $timejia));
// 								$today_end = strtotime(date("Y-m-d", strtotime("+1 day")));

                                if ($timejia < $times) {
                                    $btsr_money = $MemberWalletModel->getOneUserWallet($value['uid'], $value['cid']);
                                    //The daily income of the current financial project
                                    $jiangli = round($value['buy_number'] * $value['rate'] * 0.01 / $value['licai_day'], 5);
                                    //Add daily income into log
                                    if (MemberWalletModel::zengjiakeyong($value['uid'], $value['cid'], $jiangli)) {
                                        // write log
                                        MemberWalletLogModel::log($value['uid'], $value['cid'], $jiangli, $btsr_money['balance'], $btsr_money['balance'] + $jiangli, 10, 'Wealth Management Daily Income', 'Daily income of financial management', $value['id'], $timejia);
                                    }
                                }
                            }else{
                                $output->writeln('The last day');
//            Last day to return principal
                                if ($value['end_time'] <= $times) {
                                $btsr_money = $MemberWalletModel->getOneUserWallet($value['uid'], $value['cid']);
                                //The daily income of the current financial project
                                $jiangli = round($value['buy_number'] * $value['rate'] * 0.01 / $value['licai_day'], 5);
                                //Add daily income into log
                                if (MemberWalletModel::zengjiakeyong($value['uid'], $value['cid'], $jiangli)) {
                                        // write log
                                        MemberWalletLogModel::log($value['uid'], $value['cid'], $jiangli, $btsr_money['balance'], $btsr_money['balance'] + $jiangli, 10, 'Wealth Management Daily Income', 'Daily income of financial management', $value['id'],$timejia);
                                    }
                                $btsr_money = $MemberWalletModel->getOneUserWallet($value['uid'], $value['cid']);
                                if (MemberWalletModel::zengjiakeyong($value['uid'], $value['cid'], $value['buy_number'])) {
                                   // write log
                                    MemberWalletLogModel::log($value['uid'], $value['cid'], $value['buy_number'], $btsr_money['balance'], $btsr_money['balance'] + $value['buy_number '], 11, 'Return the financing principal', 'Return of financing principal', $value['id'],$timejia);
                                }
                                //change state
                                    $ManageMoneyListModel->where(array('id' => $value['id']))->save(array('status' => 2, 'updated_at' => time()));
                                }
                            }
                        }else{
                            $output->writeln('I have received it today');
                        }
                    }
                }
// 				commit the transaction
                Db::commit();
                $output->writeln('financial income to account');
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                $output->writeln('Financial income failed');
            }

        }
    }

}