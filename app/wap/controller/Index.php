<?php
namespace app\wap\controller;
use app\api\controller\Base;
use think\facade\Db;
use think\facade\View;

class Index extends Base
{
    public function index(){
       $this->set_seo('Home');
        View::assign('nav','index');
        return view();
    }
    public function exchange(){
        $this->set_seo('Ring vibration area');
        View::assign('nav','exchange');
        return view();
    }
    public function login(){
        $this->set_seo('login');
        return view();
    }
    public function register(){
        $this->set_seo('register');
        return view();
    }
    public function findpassword(){
        $this->set_seo('Retrieve password');
        return view();
    }
    public function classify(){
        $this->set_seo('category');
        View::assign('nav','classify');
        return view();
    }
    public function shop_cat(){
        $this->set_seo('shopping cart');
        View::assign('nav','shop_cat');
        return view();
    }
    public function brand(){
        $this->set_seo('Brand Street');
        return view();
    }
    public function news(){
        $this->set_seo('News');
        return view();
    }
    public function news_detail(){
        $this->set_seo('news details');
        return view();
    }
    public function notice_detail(){
        $this->set_seo('Announcement details');
        return view();
    }
    public function special_offer(){
        $this->set_seo('Promotions');
        return view();
    }
    public function street(){
        $this->set_seo('Shop Street');
        return view();
    }
    public function spell_index(){
        $this->set_seo('Group Homepage');
        return view();
    }
    public function spell_shop(){
        $this->set_seo('Mobile phone number');
        return view();
    }
    public function search(){
        $this->set_seo('Search');
        return view();
    }
    public function seconds_kill(){
       $this->set_seo('Seckill');
        return view();
    }
    public function goods_info(){
        $this->set_seo('Product details');
        return view();
    }
    public function store_about(){
        $this->set_seo('Store details');
        return view();
    }
    public function goods_list(){
        $this->set_seo('Product list');
        return view();
    }
    public function store_goods_class(){
        $this->set_seo('hot category');
        return view();
    }
    public function store_goodslist(){
        $this->set_seo('Store product list');
        return view();
    }
    public function search_shop(){
        $this->set_seo('Search list');
	    return view();
	}
}
