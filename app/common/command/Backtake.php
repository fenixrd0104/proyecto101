<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use app\common\model\MemberWalletLogModel;
class Backtake extends Command
{
    protected function configure()
    {
        $this->setName('Backtake')->setDescription('back take');
    }


    protected function execute(Input $input, Output $output)
    {
        /* Pay for the goods The total amount of the payment will be returned to the payer one week after the payment for the order is successful. */
        $shop_fanben = Db::name('config')->where('name','shop_fanben')->value('value');
        $time = time() - ($shop_fanben * 24 *3600);
        $order = Db::name('order')->where(['top_cate'=>2,'pay_status'=>1,'is_backtake'=>0,'pay_code'=>1])->where( 'order_status','<>',5)->where('pay_time','<',$time)->select();
        foreach ($order as $k => $v) {
            $money = Db::name('member')->where('id',$v['take_id'])->value('money');
            Db::name('member')->where('id',$v['take_id'])->inc('money',$v['total_amount'])->update();
            Db::name('order')->where('order_id',$v['order_id'])->update(['is_backtake'=>1]);
            $MemberWalletLogModel = new MemberWalletLogModel();
            $MemberWalletLogModel->log($v['take_id'],$v['total_amount'],$money,$money + $v['total_amount'],19,'Return to pay',$v['order_id' ]);
        }
        $output->writeln('Complete|Return to pay money ok!!!!');
    }

}