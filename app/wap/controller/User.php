<?php
namespace app\wap\controller;
use app\api\controller\Base;
use think\facade\Db;
use think\facade\View;

class User extends Base {
  public function __construct()
  {
    $uid=get_uid();

    if(!$uid){
      $this->set_seo('login');
      echo View::fetch('index/login');
      exit;
    }
  }
public function my(){
    $this->set_seo('personal center');
    View::assign('nav','my');
    return view();
  }
  // backup
public function my1(){
    $this->set_seo('my');
    View::assign('nav','my1');
    return view();
  }
  public function my_setting(){
    $this->set_seo('set');
    return view();
  }
  public function my_personal(){
    $this->set_seo('profile');
    return view();
  }
  // currency exchange
    public function exchange(){
      $this->set_seo('Tongdu');
      return view();
    }
    // auction application
    public function auction(){
      $this->set_seo('Auction application');
      return view();
    }
    // auction application
    public function auction_take(){
      $this->set_seo('Auction Pickup');
      return view();
    }
    // auction
    public function my_auction(){
      $this->set_seo('My auction');
      return view();
    }
    // auction hall
    public function auction_hall(){
      $this->set_seo('Auction Hall');
      View::assign('nav','auction_hall');
    return view();
  }
  // auction hall
    public function auction_detail(){
      $this->set_seo('Auction details');
      return view();
    }
    public function nickname(){
      $this->set_seo('Modify nickname');
      return view();
    }
    public function my_password(){
      $this->set_seo('Modify login password');
      return view();
    }
    public function my_paypassword(){
      $this->set_seo('Modify payment password');
      return view();
    }
    public function my_phone(){
      $this->set_seo('Modify mobile phone number or email');
      return view();
    }
    public function my_getphone(){
      $this->set_seo('Modify mobile phone number or email');
      return view();
    }
    public function my_bd_email(){
      $this->set_seo('Bind mailbox');
    return view();
  }
  public function my_bd_phone(){
    $this->set_seo('Bind mobile phone number');
        return view();
      }
      public function address(){
        $this->set_seo('shipping address');
        return view();
      }
      public function my_focus(){
        $this->set_seo('My Favorites');
        return view();
      }
      public function my_friend(){
        $this->set_seo('my friend');
        return view();
      }
      public function my_invite(){
        $this->set_seo('Invite friends');
        return view();
      }
      public function my_agent(){
        $this->set_seo('Voucher purchase');
        return view();
      }
      public function my_agent_detail(){
        $this->set_seo('recharge record');
    return view();
  }
  public function pool_withdrawal(){
   $this->set_seo('Withdrawal');
       return view();
     }
     public function pool_withdrawal_list(){
       $this->set_seo('Withdrawal log');
       return view();
     }
     public function pool_recharge(){
       $this->set_seo('Balance recharge');
       return view();
     }
     public function pool_recharge_list(){
       $this->set_seo('recharge log');
       return view();
     }
     public function my_pool_yue(){
       $this->set_seo('US log');
       return view();
     }
     public function wallet(){
       $this->set_seo('My KRC');
       return view();
     }
     public function wallet1(){
       $this->set_seo('My USDT');
    return view();
  }
  public function wallet2(){
    $this->set_seo('My XXX gift coins');
        return view();
      }
      public function wallet3(){
        $this->set_seo('My voucher');
        return view();
      }
      public function wallet4(){
        $this->set_seo('My coupon');
        return view();
      }
      public function wallet5(){
        $this->set_seo('My original shares');
        return view();
      }
      public function pool_fuhua(){
        $this->set_seo('Incubating Hash Log');
        return view();
      }
      public function pool_fhtransfer(){
        $this->set_seo('transfer');
        return view();
      }
      public function my_pool_xiaof(){
        $this->set_seo('Resonance hashrate log');
    return view();
  }
  public function my_pool_repeat(){
    $this->set_seo('Hashrate log');
        return view();
      }
      public function my_pool_jijin(){
        $this->set_seo('Fund computing power log');
        return view();
      }
      public function message_notice(){
        $this->set_seo('Message Center');
        return view();
      }
      public function message_detail(){
        $this->set_seo('My message');
        return view();
      }
      public function order_list(){
        $this->set_seo('My order');
        return view();
      }
      public function order_return(){
        $this->set_seo('refund/after-sale');
        return view();
      }
      public function order_return_detail(){
        $this->set_seo('refund details');
    return view();
  }
  public function order_submit_detail(){
    $this->set_seo('Submit work order');
    return view();
  }
  public function order_aplyreturn(){
    $this->set_seo('Apply for a refund');
    return view();
  }
  public function order_aplytuihuo(){
    $this->set_seo('Apply for a refund');
    return view();
  }
  public function order_comment(){
    $this->set_seo('Order evaluation');
    return view();
  }
  public function order_wuliu(){
    $this->set_seo('View logistics');
    return view();
  }
  public function recharge(){
    $this->set_seo('Recharge');
    return view();
  }
  public function withdraw(){
    $this->set_seo('Apply for withdrawal');
    return view();
  }
  public function add_address(){
    $this->set_seo('Add delivery address');
        return view();
      }
      public function address_select(){
        $this->set_seo('Select delivery address');
        return view();
      }
      public function buy_now(){
        $this->set_seo('Fill in the order');
        return view();
      }
      public function buy_order(){
        $this->set_seo('Fill in the order');
        return view();
      }
      public function buy_submit(){
        $this->set_seo('pay, submit order');
        return view();
      }
      public function buy_submit_order(){
        $this->set_seo('pay, submit order');
        return view();
      }
      public function order_detail(){
        $this->set_seo('order details');
    return view();
  }
  public function agreement(){
    $this->set_seo('Agreement Confirmation');
    return view();
  }
}
