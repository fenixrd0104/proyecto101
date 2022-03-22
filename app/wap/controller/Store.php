<?php
namespace app\wap\controller;
use app\api\controller\Base;
use think\facade\Db;
use think\facade\View;

class store extends Base {  
  public function __construct()
  {
    $uid=get_uid();
    
    if(!$uid){
     $this->set_seo('login');
      echo View::fetch('index/login');
      exit;
    }
  }
  public function store(){
    $this->set_seo('Business management');
    return view();
  }
  public function open_sell(){
    $this->set_seo('I want to open a store');
    return view();
  }
  public function open_info(){
    $this->set_seo('Merchant settled in');
    return view();
  }
  public function store_info_detail(){
    $this->set_seo('Business profile');
    return view();
  }
  
  public function store_info(){
    $this->set_seo('Store details');
    return view();
  }
  public function pool_liushui(){
    $this->set_seo('stock');
    return view();
  }
  public function store_baodan(){
    $this->set_seo('order');
    return view();
  }
  public function store_shop(){
    $this->set_seo('shop');
    return view();
  }
  public function store_sales(){
    $this->set_seo('Sales log');
    return view();
  }
  public function store_water(){
    $this->set_seo('Inventory log');
    return view();
  }
  public function edit_store(){
    $this->set_seo('Modify data');
    return view();
  }
  public function store_goods(){
    $this->set_seo('Commodity management');
    return view();
  }
  public function goods_release(){
    $this->set_seo('Post product');
    return view();
  }
  public function store_order(){
    $this->set_seo('order');
    return view();
  }

  public function my_fenxiao(){
    $this->set_seo('distribution center');
    return view();
  }
  public function fenxiao_team(){
    $this->set_seo('my team');
    return view();
  }
  public function set_store(){
    $this->set_seo('Store settings');
    return view();
  }
  public function fencheng(){
    $this->set_seo('divided into details');
    return view();
  }  
  public function mystore(){
  $this->set_seo('My Micro Store');
      return view();
    }
    public function fenxiao_order(){
      $this->set_seo('distribution order');
      return view();
    }
    public function mycard(){
      $this->set_seo('My business card');
      return view();
    }
    public function fenxiao_goods(){
      $this->set_seo('List of distribution products');
      return view();
    }
    public function ranking_money(){
      $this->set_seo('Distribution leaderboard');
      return view();
    }
    public function ranking_offline(){
      $this->set_seo('Distribution leaderboard');
    return view();
  }  
}
