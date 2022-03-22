<?php
namespace app\common\command;

use app\common\model\MemberWalletLogModel;
use app\common\model\MemberWalletModel;
use app\common\model\Member;
use app\common\service\Users;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;


class Wallet extends Command
{


    protected function configure()
    {
        $this->setName('wallet')->setDescription('wallet 5min upadte');
    }


    protected function execute(Input $input, Output $output)
    {


        $config = $this->configLists();
        cache('db_config_data',$config);
        config($config,'config');

        //recharge callback processing record
                $User = new Users();
        
                $data = $User->qbapiGet('/eth/recharge',['page'=>'1','pagesize'=>100]);
                if(isset($data['data'])){
                    foreach ($data['data'] as $vale){
                        //rawok == 1 is successful account
                      // pe($vale);
                        if($vale['rawok']==1){
                            if($vale['currency'] == 'US'){
                                $map['status'] = 2;
                                $map['ti_id'] = $vale['tx'];
        
                                if(!Db::name('recharges')->where($map)->find()){
                                    //Query who owns this wallet
                                    $member = Db::name('member')->where([['cz_address','=',$vale['address']]])->find();
                                    $uid = $member['id'];
                                    Db::startTrans();
                                    try {
                                        //Add record recharges
                                $id = Db::name('recharges')->insert([
                                    'uid'=>$uid,
                                    'ordnum'=>'RE20'.time() . str_pad(mt_rand(100000000, 999999999), 5, '0', STR_PAD_LEFT),
                                    'chongzhi_url'=>$vale['address'],
                                    'ti_id'=>$vale['tx'],
                                    'money'=>$vale['balance'],
                                    'status'=>2,
                                    'mark'=>'online recharge',
                                    'type'=>2,
                                    'pid'=>0,
                                    'currency'=>'US',
                                    'created_at'=>date('Y-m-d H:i:s',time()),
                                    'updated_at'=>date('Y-m-d H:i:s',time())
                                ],true);

                               //Add account balance
                                Member::Onefield($uid,'money','up',$vale['balance']);
                                // write log
                                MemberWalletLogModel::log($uid,$vale['balance'],$member['money'],$member['money']+$vale['balance'],7,'Online recharge',$uid) ;
                                // send message
                                Db::name('member_msg')->insert(
                                    [
                                        'receive_uid' => $uid,
                                        'send_uid' => 0,
                                        'title'=>'Online recharge to account'.$vale['balance'],
                                        'content'=>'Online recharge to account'.$vale['balance'],
                                        'status'=>0,
                                        'send_time'=>time(),
                                    ]
                                );
                                // commit the transaction
                                Db::commit();

                            } catch (\Exception $e) {
                                echo $e->getMessage();
                                // rollback transaction
                                Db::rollback();
                            }
                        }
                    }

                    if($vale['currency'] == 'USDT'){
                        $map['status'] = 2;
                        $map['ti_id'] = $vale['tx'];

                        if(!Db::name('recharges')->where($map)->find()){
                           //Query who owns this wallet
                            $member = Db::name('member')->where([['cz_address','=',$vale['address']]])->find();
                            $uid = $member['id'];
                            $usdt = config('config.usdt_bili');
                            $balance = $vale['balance'] * $usdt;
                            Db::startTrans();
                            try {
                                //add record recharges
                                $id = Db::name('recharges')->insert([
                                    'uid'=>$uid,
                                    'ordnum'=>'RE20'.time() . str_pad(mt_rand(100000000, 999999999), 5, '0', STR_PAD_LEFT),
                                    'chongzhi_url'=>$vale['address'],
                                    'ti_id'=>$vale['tx'],
                                    'money'=>$vale['balance'],
                                    'status'=>2,
                                    'mark'=>$vale['balance'].'USDT兑换'.$balance.'US',
                                    'type'=>2,
                                    'pid'=>0,
                                    'currency'=>'USDT',
                                    'created_at'=>date('Y-m-d H:i:s',time()),
                                    'updated_at'=>date('Y-m-d H:i:s',time())
                                ],true);

                                //Add account balance
                                Member::Onefield($uid,'money','up',$balance);
                                // write log
                                MemberWalletLogModel::log($uid,$balance,$member['money'],$member['money']+$balance,7,$vale['balance'].'USDT exchange'.$balance.'US ',$uid);
                                // send message
                                Db::name('member_msg')->insert(
                                    [
                                        'receive_uid' => $uid,
                                        'send_uid' => 0,
                                        'title'=>'Online recharge to account'.$balance,
                                        'content'=>'online recharge to account'.$balance,
                                        'status'=>0,
                                        'send_time'=>time(),
                                    ]
                                );

                                // commit the transaction
                                Db::commit();

                            } catch (\Exception $e) {
                                echo $e->getMessage();
                                // rollback transaction
                                Db::rollback();
                            }
                        }
                    }

                    }
                }

            }

        $output->writeln('otc 15min back ok');
    }


    /**
     * Get the list of configurations in the database
     * @return array configuration array
     */
    public static function configLists(){
        $map  = array('status' => 1);
        $data = Db::name('Config')->where($map)->field('type,name,value')->select();

        $config = array();
        if($data){
            foreach ($data as $value) {
                $config[$value['name']] = self::parse($value['type'], $value['value']);
            }
        }
        return $config;
    }

    private static function parse($type, $value){
        switch ($type) {
            case 3: //parse array
                $array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
                if(strpos($value,':')){
                    $value  = array();
                    foreach ($array as $val) {
                        list($k, $v) = explode(':', $val);
                        $value[$k]   = $v;
                    }
                }else{
                    $value =    $array;
                }
                break;
        }
        return $value;
    }





}