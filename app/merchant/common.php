<?php
use think\facade\Db;

/**
 * Parse characters into an array
  * @param $str
  */
 function parseParams($str)
 {
     $arrParams = [];
     parse_str(html_entity_decode(urldecode($str)), $arrParams);
     return $arrParams;
 }
 
 
 /**
  * Descendant tree for menu organization
  * @param $param
  * @param int $pid
  */
 function subTree($param, $pid = 0)
 {
     $res = [];
     foreach($param as $key=>$vo){
         if( $pid == $vo['pid'] ){
             $vo['sub']=subTree($param, $vo['id']);
             $res[] = $vo;
         }
     }
     return $res;
 }
 
 
 /**
  * record log
  * @param [type] $uid [userid]
  * @param [type] $username [username]
  * @param [type] $description [description]
  * @param [type] $status [status]
  * @return [type] [description]
 */
function writelog($uid,$username,$description,$status)
{

    $data['admin_id'] = $uid;
    $data['admin_name'] = $username;
    $data['description'] = $description;
    $data['status'] = $status;
    $data['ip'] = request()->ip();
    $data['add_time'] = time();
    $log = Db::name('Log')->insert($data);

}


/**
* Organize the menu tree method
 * @param $param
 * @return array
 */
function prepareMenu($param)
{
    $parent = []; //parent class
    $child = []; // child class

    foreach($param as $key=>$vo){

        $vo=[
            'id'=>$vo['id'],
            'name'=>$vo['name'],
            'title'=>$vo['title'],
            'icon'=>$vo['css'],
            'pid'=>$vo['pid'],
        ];
        if($vo['pid'] == 0){
            $vo['jump'] = '#';
            $parent[] = $vo;
        }else{
            $vo['jump'] = $vo['name']; //Jump address
            $child[] = $vo;
        }
    }

    foreach($parent as $key=>$vo){
        foreach($child as $k=>$v){
            if($v['pid'] == $vo['id']){
                $parent[$key]['list'][] = $v;
            }
        }
    }
    unset($child);
    return $parent;
}


/**
 * format byte size
  * @param number $size number of bytes
  * @param string $delimiter Number and unit delimiter
  * @return string formatted size with units
  */
 function format_bytes($size, $delimiter = '') {
     $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
     for ($i = 0; $size >= 1024 && $i < 5; $i++) {
         $size /= 1024;
     }
     return $size . $delimiter . $units[$i];
 }
 
 //Get the Cartesian product of the specification
function combineDika($arr)
{
    $base = current($arr);
    while (next($arr)) {
        $next = current($arr);
        $dika = array();
        foreach ($base as $k => $v) {
            foreach($next as $k1 => $v1) {
                $dika[] = $v . '_' . $v1;
            }
        }
        $base = $dika;
    }
    return $base;
}

function serialize51($value){
    return  is_scalar($value) ? $value : 'think_serialize:' . serialize($value);
}
function unserialize51($value){
    return  0 === strpos($value, 'think_serialize:') ? unserialize(substr($value, 16)) : $value;

}


