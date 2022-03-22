<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | console configuration
// +------------------------------------------------------------ -----------------------
return [
    // Execute user (invalid under Windows)
    'user' => null,
    // instruction definition
    'commands' => [
        'statics' => 'app\common\command\Statics',
        'wallet' => 'app\common\command\Wallet',
        'price' => 'app\common\command\Price',
        'backtake'=>'app\common\command\Backtake',
        'auction' =>'app\common\command\Auction',
        'auctionmsg' =>'app\common\command\Auctionmsg',
        'cleared' =>'app\common\command\Cleared'
    ],
];
