<?php
namespace app\common\command;

//use app\common\model\Member;
//use app\common\model\MemberWalletLogModel;
//use app\common\model\ManageMoneyListModel;
//use app\common\model\MemberWalletModel;
use app\common\model\Member;
use app\common\model\MemberWalletLogModel;
use app\common\service\Users;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class Statics extends Command
{
    protected function configure()
    {
        $this->setName('statics')->setDescription('The static gains');
    }


    protected function execute(Input $input, Output $output)
    {
        $config = $this->configLists();
        cache('db_config_data',$config);
        config($config,'config');


        /*------------------------------static income--------------------------------------*/
        $member=Db::name('member')->where([['pool_sale|pool_consumption','>',0],['status','=','1']])->select();
        foreach ($member as $key =>$value){
            $consume_rate = config('config.consume_rate');//Consume pool ratio
            $sell_rate = config('config.sell_rate');//Sell pool ratio
            // start transaction
            Db::startTrans();
            try {
            //sale pool
            if($value['pool_sale']&&$value['pool_sale']>0){
                $pool_sale=round($sell_rate*$value['pool_sale']*0.01,2);
                if($pool_sale==0){
                    $pool_sale=$value['pool_sale'];
                }
                $output->writeln($pool_sale);
                // minus the sales pool
                Member::Onefield($value['id'],'pool_sale','down',$pool_sale);
                MemberWalletLogModel::log($value['id'],$pool_sale,$value['pool_sale'],$value['pool_sale']-$pool_sale,67,'Release and reduce sales pool',$value['id' ]);
// 				//Change balance //Add log
                Member::Onefield($value['id'],'pool_hatch','up',$pool_sale);
                MemberWalletLogModel::log($value['id'],$pool_sale,$value['pool_hatch'],$value['pool_hatch']+$pool_sale,63,'Release sales pool to increase incubation computing power',$value[ 'id']);
//				// $this->biangeng($value['id'],$pool_sale,'pool_sale','pool_hatch');
                 $output->writeln('Successful sales pool');
            }
            //consumer pool
            if($value['pool_consumption']&&$value['pool_consumption']>0){
                $pool_consumption=round($consume_rate*$value['pool_consumption']*0.01,2);
                if($pool_consumption==0){
                    $pool_consumption=$value['pool_consumption'];
                }
                $output->writeln($pool_consumption);
                // reduce the consumption pool
                Member::Onefield($value['id'],'pool_consumption','down',$pool_consumption);
                MemberWalletLogModel::log($value['id'],$pool_consumption,$value['pool_consumption'],$value['pool_consumption']-$pool_consumption,66,'Release resonance hashrate reduction',$value['id ']);
// //Add hatching pool
                Member::Onefield($value['id'],'pool_hatch','up',$pool_consumption);
                MemberWalletLogModel::log($value['id'],$pool_consumption,$value['pool_hatch'],$value['pool_hatch']+$pool_consumption,63,'Release resonance computing power to increase incubation computing power',$value ['id']);
                $output->writeln('Consumption pool success');
            }
                // commit the transaction
                Db::commit();
                $output->writeln('success');
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                $output->writeln('failed');
            }
        }

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