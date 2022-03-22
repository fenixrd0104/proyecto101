<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
class Cleared extends Command
{
    protected function configure()
    {
        $this->setName('Cleared')->setDescription('Cleared');
    }


    protected function execute(Input $input, Output $output)
    {
        $res=Db::name('get_record')->where('status',0)->select()->toArray();
        foreach ($res as $key => $value) {
            $chao_time=$value['create_time']+86400;
            if(time()>=$chao_time){
                Db::name('get_record')->where('id',$value['id'])->update(['status'=>2,'update_time'=>time()]);
            }
        }
        $output->writeln('Expired Clear|Doneok!!!!');
    }

}