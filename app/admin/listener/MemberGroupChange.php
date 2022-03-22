<?php

namespace app\admin\listener;

use app\common\service\Settlement;

class MemberGroupChange
{
    public function handle($data)
    {
	    $Settlement = new Settlement(config('config'));
        $Settlement->shengji($data['uid'],$data['group_id']);
        return true;
    }    
}
