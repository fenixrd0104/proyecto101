<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
class Auctionmsg extends Command
{
    protected function configure()
    {
        $this->setName('Auctionmsg')->setDescription('Auction msg');
    }


    protected function execute(Input $input, Output $output)
    {
        /* Auction reminder timed to execute every minute */
        $map = [];
        $map[] = ['is_msg','=',0];
        $auction_msg = Db::name('auction_msg')->where($map)->select();
        foreach ($auction_msg as $k => $v) {
            //to be turned on
            if($v['msg_time'] <= time()){
                Db::name('auction_msg')->where('id',$v['id'])->update(['is_msg'=>1]);
                $params['code'] = '';
                $params['content'] = 'Hello, '.$v['goods_name'].' will start the auction in '. $v["time"] . ' minutes, please log in to participate. ';
                if ($v['mobile']) {
                    $info = \app\common\service\Msg::send_sms(1,$v['country_code'].$v['mobile'],$params);
                }
            }
        }
        $output->writeln('Complete|Auction ok!!!!');
    }

}