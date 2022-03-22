<?php

namespace app\admin\controller;
use app\admin\model\ConfigModel;
use think\facade\Db;
use Swoole\Client;


class Config extends Base
{

    /**
     * [index configuration list]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['title'] = ['like',"%" . $key . "%"];          
        }       
        $config = new ConfigModel();
        $nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',10);
        $count = $config->getAllCount($map);//Calculate total pages
        $list = $config->getAllConfig($map, $nowpage, $limits);

        return json(['code'=>1,'data'=>['lists'=>$list,'count'=>$count],'msg'=>'']);

    }
    

    /**
     * [add_config add configuration]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function add_config()
    {
        if(request()->isPost()){

            $param = input('post.');
            $config = new ConfigModel();
            $flag = $config->insertConfig($param);
            cache('db_config_data',null);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return json(['code'=>1,'data'=>['type_lists'=>new \ArrayObject(config('config.config_type_list')),'group_lists'=>config('config.config_group_list')],'msg'=>'']);

    }


    /**
     * [edit_config edit configuration]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function edit_config()
    {
        $config = new ConfigModel();
        if(request()->isPost()){
            $param = input('post.');
            $flag = $config->editConfig($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $config->getOneConfig($id);
        return json(['code'=>1,'data'=>['info'=> $info,'type_lists'=>new \ArrayObject(config('config.config_type_list')),'group_lists'=>config('config.config_group_list')],'msg'=>'']);

    }


    /**
     * [del_config delete config]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function del_config()
    {
        $id = input('param.id');
        $config = new ConfigModel();
        $flag = $config->delConfig($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * [user_state configuration state]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function status_config()
    {
        $id = input('param.id');
        $status = Db::name('config')->where(array('id'=>$id))->value('status');//Judging the current state
        if($status==1)
        {
            $flag = Db::name('config')->where(array('id'=>$id))->update(['status'=>0]);
            return json(['code' => 1, 'data' =>[], 'msg' => 'banned']);
        }
        else
        {
            $flag = Db::name('config')->where(array('id'=>$id))->update(['status'=>1]);
            return json(['code' => 1, 'data' => [], 'msg' => 'Turned on']);
        }
    
    }


    /**
     * [Get the configuration parameters of a label]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function group() {
        $type = config('config.config_group_list');
        $list=[];
        foreach ($type as $key => $v){
            $list[] = Db::name("Config")->where(array('status'=>1,'group'=>$key))->field('id,name,title,extra,value,remark,type')->order('sort asc,id asc')->select();
        }
        return json(['code'=>1,'data'=>['lists'=>$list,'title'=>$type],'msg'=>'']);


    }



    /**
     * [Batch save configuration]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function save($config){
        if($config && is_array($config)){
            foreach ($config as $name => $value) {
                $map = array('name' => $name);
                Db::name('Config')->where($map)->update(['value'=> $value]);
            }
        }
        cache('db_config_data',null);
//        $this->setSwoole();
			return json(['code'=>1,'data'=>[],'msg'=>'saved successfully']);
    }

    public function setSwoole(){
        $swoole_server_ip = config('app.swoole_server_ip');
        $swoole_server_port = config('app.swoole_server_port');
        $client = new Client(SWOOLE_SOCK_TCP);
        if (!$client->connect($swoole_server_ip, $swoole_server_port, 0.5)) {
            throw new Exception('Task error: Unable to connect to the TCP service, please contact the administrator!');
        }
        $data = ['type' => 'update_config', 'order' => []];
        if (!$client->send(json_encode($data))) {
            throw new Exception('Task error: task delivery failed, please contact the administrator!');
        }
        //close the connection
        $client->close();
    }

}