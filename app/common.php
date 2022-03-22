<?php
use think\facade\Db;


/**
* String interception, support Chinese and other encodings
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 * Call the API interface method of the system (static method)
 * api('User/getName','id=5'); Call the getName method of the User interface of the public module
 * api('Admin/User/getName','id=5'); Call the User interface of the Admin module
 * @param string $name format [module name]/interface name/method name
 * @param array|string $vars parameter
 */
/*function api($name,$vars=array()){
    $array     = explode('/',$name);
    $method    = array_pop($array);
    $classname = array_pop($array);
    $module    = $array? array_pop($array) : 'common';
    $callback  = 'app\\'.$module.'\\Api\\'.$classname.'Api::'.$method;
    if(is_string($vars)) {
        parse_str($vars,$vars);
    }
    return call_user_func_array($callback,$vars);
}*/


/**
 * Get configured group
 * @param string $group Configure grouping
 * @return string
 */
/*function get_config_group($group=0){
    $list = config('config_group_list');
    return $group?$list[$group]:'';
}*/

/**
 * Get the type of configuration
 * @param string $type configuration type
 * @return string
 */
/*function get_config_type($type=0){
    $list = config('config_type_list');
    return $list[$type];
}*/


 // Analyze enumeration type configuration value Format a:name1,b:name2
function parse_config_attr($string) {
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if(strpos($string,':')){
        $value  =   array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    }else{
        $value  =   $array;
    }
    return $value;
}

//Generate the QR code of the URL Return the image address
/*function Qrcode($token, $url, $size = 8){

    $md5 = md5($token);
    $dir = date('Ymd'). '/' . substr($md5, 0, 10) . '/';
    $patch = 'qrcode/' . $dir;
    if (!file_exists($patch)){
        mkdir($patch, 0755, true);
    }
    $file = 'qrcode/' . $dir . $md5 . '.png';
    $fileName =  $file;
    if (!file_exists($fileName)) {

        $level = 'L';
        $data = $url;
        QRcode::png($data, $fileName, $level, $size, 2, true);
    }
    return $file;
}
*/


/**
 * Loop through directories and files
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name) {
    $result = false;
    if(is_dir($dir_name)){
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DIRECTORY_SEPARATOR . $item)) {
                        delete_dir_file($dir_name . DIRECTORY_SEPARATOR . $item);
                    } else {
                        unlink($dir_name . DIRECTORY_SEPARATOR . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}



//time format 1
function formatTime($time) {
    $now_time = time();
    $t = $now_time - $time;
    $mon = (int) ($t / (86400 * 30));
    if ($mon >= 1) {
        return 'one month ago';
    }
    $day = (int) ($t / 86400);
    if ($day >= 1) {
        return $day . 'day ago';
    }
    $h = (int) ($t / 3600);
    if ($h >= 1) {
        return $h . 'hour ago';
    }
    $min = (int) ($t / 60);
    if ($min >= 1) {
        return $min . 'minutes ago';
    }
    return 'just now';
}

//time format 2
function pincheTime($time) {
    $today = strtotime(date('Y-m-d')); //It's zero today
    $here = (int)(($time - $today)/86400) ;
    if($here==1){
        return 'tomorrow';
    }
    if($here==2) {
        return 'the day after tomorrow';
    }
    if($here>=3 && $here<7){
        return $here.'days';
    }
    if($here>=7 && $here<30){
        return 'one week later';
    }
    if($here>=30 && $here<365){
        return 'one month later';
    }
    if($here>=365){
        $r = (int)($here/365).'years later';
        return $r;
    }
    return 'today';
}

function getRandomString($len, $chars=null)
{
    if (is_null($chars)){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }
    mt_srand(10000000*(double)microtime());
    for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
        $str .= $chars[mt_rand(0, $lc)];
    }
    return $str;
}

function random_str($length)
{
    //Generate an array containing uppercase English letters, lowercase English letters, numbers
        $arr = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
    
        $str = '';
        $arr_len = count($arr);
        for ($i = 0; $i < $length; $i++)
        {
            $rand = mt_rand(0, $arr_len-1);
            $str.=$arr[$rand];
        }
    
        return $str;
    }
    // Verify that the phone number is valid
    function isMobile($mobile) {
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^ 17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
    }
    
    // Verify that the email address is valid
function isEmail($email) {
    return preg_match('#^[a-zA-Z0-9\._-]{2,}@[a-zA-Z0-9\._-]{2,}\.[a-z]{2,}$#', $email) ? true : false;
}


/**
 * Get the inventory of an item
 */
function getGoodsNum($goods_id, $spec_key = '')
{
    if ($spec_key) {
        $spec_num = Db::name('spec_goods')->where(['goods_id' => $goods_id, 'spec_key' => $spec_key])->value('spec_num');
    } else {
        $spec_num = Db::name('goods')->where('id', $goods_id)->value('spec_num');
    }
    return $spec_num;
}


/* Get the content of the ad slot */
//@param $id ad slot id
//@param $type gets the advertisement form single, list rotation
function get_adinfo($id,$type='single'){
    $where = [];
    $where[] = ['ad_position_id','=',$id];
    $where[] = ['start_date','<=',date("Y-m-d",time())];
    $where[] = ['end_date','>',date("Y-m-d",time())];
    $where[] = ['status','=',1];
    $where[] = ['closed','=',0];

    $ad_list = Db::name('ad')->where($where)->order("orderby asc")->select();
    
    if($type == 'single'){
        return $gg_list = Db::name('article')->where([['status','=',1],['cate_id','=',2]])->order("orderby asc,create_time desc")->select();
    }elseif($type == 'list'){
        return $ad_list;
    }elseif($type == 'article'){
        return $article = Db::name('article')->where([['status','=',1],['is_tui','=',1]])->order(['create_time'=>'desc'])->select();
    }
}


/**
 * Check if the user is logged in
  * @return integer 0 - not logged in, greater than 0 - currently logged in user ID
  * @author McDonald Miaoer <zuojiazi@vip.qq.com>
 */
function is_login()
{
    //return 1112;
    $user = session('user_auth');
    if (empty($user)) {
        return 0;
    } else {
        if(session('user_auth_sign') == data_auth_sign($user)){
            if(Db::name('member')->where('id',$user['uid'])->value('status') != 1){
                return -1;
            }else{
                return $user['uid'];
            }
        }
    }
}

function get_uid()
{
    return is_login();
}
/**
* Data signature authentication
 * @param array $data authenticated data
 * @return string signature
 * @author McDonald Miaoer <zuojiazi@vip.qq.com>
 */
function data_auth_sign($data)
{
    //check data type
    if (!is_array($data)) {
        $data = (array)$data;
    }
    ksort($data); //sort
    $code = http_build_query($data); //url encode and generate query string
    $sign = sha1($code); //Generate signature
    return $sign;
}

function pe($arr){
    echo '<pe>';
    print_r($arr);
    echo '</pe>';
    die;
}
/**
 * Check if it is a WeChat browser
 * @return boolean
 */
function is_weixin(){
    if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
        return true;
    }
    return false;
}

//Get industry classification
function getIndustry(){
    $industry = Db::name('industry')->where('parent_id',0)->select()->toArray();
    $getIndustry = [];
    foreach ($industry as $k => $v) {
        $industry[$k]['child'] = Db::name('industry')->where('parent_id',$v['id'])->select()->toArray();
        if($industry[$k]['child']){
            foreach ($industry[$k]['child'] as $kk => $vv) {
                $getIndustry[($k+1).$kk]['ids'] = $v['id'].','.$vv['id'];
                $getIndustry[($k+1).$kk]['value'] = $v['gname'].'/'.$vv['gname'];
            }
        }else{
            $getIndustry[$k+1]['ids'] = $v['id'];
            $getIndustry[$k+1]['value'] = $v['gname'];
        }
        sort($getIndustry);
    }

    return $getIndustry;
}
/**
 * Store product classification
 * @param $industry
 * @param $shop_id
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getChild($industry,$shop_id){
    $getIndustry = [];
    foreach ($industry as $k => $v) {
        $industry[$k]['child'] = Db::name('goods_category')->where('shop_id',$shop_id)->where('parent_id',$v['id'])->select()->toArray();
        if($industry[$k]['child']){
            foreach ($industry[$k]['child'] as $kk => $vv) {
                $getIndustry[($k+1).$kk]['ids'] = $v['id'].','.$vv['id'];
                $getIndustry[($k+1).$kk]['value'] = $v['name'].'/'.$vv['name'];
                $getIndustry[$k+1]['order'] = $vv['order'];
            }
        }else{
            $getIndustry[$k+1]['ids'] = $v['id'];
            $getIndustry[$k+1]['value'] = $v['name'];
            $getIndustry[$k+1]['order'] = $v['order'];
        }
//        sort($getIndustry);
    }
    return $getIndustry;
}



////Get the local real IP
/**
 * @param int $type 0 returns IP address 1 returns IPV4
 * @param bool|true $adv
 * @return mixed
 */
function get_client_ip($type = 0,$adv= true ) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($adv){
        $ip = $_SERVER['REMOTE_ADDR'];
        if(isset($_SERVER['HTTP_CDN_SRC_IP'])){
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        }elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])){
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)){
            foreach ($matches[0] AS $xip){
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)){
                    $ip = $xip;
                    break;
                }
            }
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IPaddress legal verification
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

if (!function_exists('set_phone_verify')) {
    function set_phone_verify($phone, $verify_info, $prefix = 'verify_')
    {
        session($prefix . $phone, $verify_info);
    }
}
if (!function_exists('get_phone_verify')) {
    function get_phone_verify($phone, $prefix = 'verify_')
    {
        return session($prefix . $phone);
    }
}
if (!function_exists('check_phone_verify')) {
    function check_phone_verify($phone, $verify, $prefix = 'verify_')
    {
        $verify_info = get_phone_verify($phone, $prefix);
        if(!isset($verify_info) && $verify != '8888'){
            return ['code'=>0,'msg'=>'The verification code is invalid~'];
        }
        if(time()>$verify_info['verify_expire'] && $verify != '8888'){
            return ['code'=>0,'msg'=>'Verification code expired~'];
        }

        $s_verify = $verify_info['verify'];
        if ($s_verify != $verify && $verify != '8888') {
            return ['code' => 0, 'msg' => 'Verification code error'];
        } else {
            session($prefix . $phone, null);
            return ['code' => 1, 'msg' => 'Verification code passed'];
        }
    }
}
if (!function_exists('recheck_phone_verify')) {
    function recheck_phone_verify($phone, $verify, $prefix = 'verify_')
    {
        if(!$verify){return ['code' => 0, 'msg' => 'Please fill in the verification code'];}
        
        $verify_info = get_phone_verify($phone, $prefix);

        $s_verify = $verify_info['verify'];
        if ($s_verify != $verify && $verify != '8888') {
       // if ($s_verify != $verify) {
           return ['code' => 0, 'msg' => 'Verification code error'];
        } else {

            return ['code' => 1,'country_code'=>$verify_info['country_code'], 'msg' => 'Verification code passed'];
        }
    }
}
if (!function_exists('del_phone_verify')) {
    function del_phone_verify($phone, $verify, $prefix = 'verify_')
    {
        session($prefix . $phone, null);
    }
}