<?php

namespace app\merchant\listener;

use app\common\service\Settlement;

class MemberReferidReg
{
    public function handle($data)
    {
	    $Settlement = new Settlement(config('config'));
        $Settlement->reg($data['uid'],$data['referid']);
        return true;
    }    
}
