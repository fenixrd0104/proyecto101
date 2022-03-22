<?php

namespace app\admin\controller;

use app\admin\model\AutoReply;
use app\admin\model\WxinMenu;
use app\admin\model\ReplyInfo;
use think\facade\Db;
use think\exception\ValidateException;

class Weixin extends Base
{
    //custom reply list
    public function autoreply()
    {
        $list = Db::name('wxin_autoreply')->field('id,rule_name,TRIM(BOTH "|" FROM key_word) as key_word,content,status')->order('id desc')->paginate(15);
        $count = $list->toArray();
        return json(['code' => 1, 'data' => ['count' => $count['total'], 'list' => $count['data']], 'msg' => '']);
    }

    //custom reply add
    public function autoreply_add()
    {
        if ($this->request->isPost()) {
            $param = input('post.');
            try {
                $this->validate($param, 'WxinVaildate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
            $reply = new AutoReply();
            $info = $reply->addReply($param);
            if ($info['code']) {
                $auto_query = Db::name('wxin_autoreply')->select();
				return json(['code' => 1, 'data' => $auto_query, 'msg' => 'Added successfully']);
            } else {
                return json(['code' => 0, 'data' => [], 'msg' => 'Add failed']);
            }
        }
    }

	// custom reply edit
    public function autoreply_edit()
    {
        if ($this->request->isPost()) {
            $param = input('post.');
            try {
                $this->validate($param, 'WxinVaildate');
            } catch (ValidateException $e) {
                // validation failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
            $param['key_word']=trim($param['key_word']);
            $param['key_word']='|'.trim($param['key_word'],'|').'|';
            if(Db::name('wxin_autoreply')->update($param)){
                return json(['code' => 1, 'data' => [], 'msg' => 'update successful']);
            }else{
                return json(['code' => 0, 'data' => [], 'msg' => 'update failed']);
            }
        } else {
            $id = input('id');
            $row = Db::name('wxin_autoreply')->field('id,rule_name,TRIM(BOTH "|" FROM key_word) as key_word,content,status')->find($id);
            return json(['code' => 1, 'data' => $row, 'msg' => 'edit']);
        }
    }

	// custom reply delete

    public function autoreply_del()
    {
        $id = input('id');
        $del = Db::name('wxin_autoreply')->delete($id);
        if ($del) {
            return json(['code' => 1, 'data' => $del, 'msg' => 'Delete successful']);
        } else {
            return json(['code' => 0, 'data' => $del, 'msg' => 'deletion failed']);
        }
    }

		// custom reply change status
    public function change_status()
    {
        $status = input('status');
        $id = input('id');
        if ($status == 1) {
            $status =1;
            $su = 'Enable success';
            $err = 'Enable failed';
        } else {
            $status = 0;
            $su = 'Disable success';
            $err = 'Disable failed';
        }
        $row = Db::name('wxin_autoreply')->where(array('id' => $id))->update(array('status' => $status));
        if ($row) {
            return json(['code' => 1, 'data' => [], 'msg' => $su]);
        } else {
            return json(['code' =>1, 'data' => [], 'msg' => $err]);
        }
    }


	// menu settings list
    public function menu(WxinMenu $menu)
    {
        $list = $menu->menulist();
        return json(['code' => 1, 'data' => ['list' => $list], 'msg' => '']);
    }
    //Menu settings add
    public function add_menu()
    {
        if ($this->request->isPost()) {
            $param = input('post.');
            try {
                $this->validate($param, 'WmenuVaildate');
            } catch (ValidateException $e) {
                // validation failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
            $model = new WxinMenu();
            $result = $model->addmenu($param);
            if ($result['code']) {
                $list = $model->menulist();
                return json(['code' => 1, 'data' => $list, 'msg' => 'Added successfully']);
            } else {
                return json(['code' => 0, 'data' => [], 'msg' => $result['msg']]);
            }
        } else {
            $model = new WxinMenu();
            $menu = $model->optionlist(array('pid' => 0));
            return json(['code' => 1, 'data' => ['menu'=>$menu], 'msg' => '']);

        }

    }

    public function edit_menu(WxinMenu $model)
    {
        if ($this->request->isPost()) {
            $param = input('post.');
            try {
                $this->validate($param, 'WmenuVaildate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }

            $info=$model->editMenu($param);
            if($info['code']){
                $list = $model->menulist();
				return json(['code' => 1, 'data' => $list, 'msg' => 'Added successfully']);
            }else{
                if(!$info['msg'])$info['msg']='Nothing has been modified';
                return json(['code' => 0, 'data' => [], 'msg' => $info['msg']]);
            }
        } else {

            $id = input('get.id');
            $option = $model->optionlist(array(['pid','=', 0],[ 'id','<>', $id]));
            $row = $model->rowWxin($id);
            return json(['code' => 1, 'data' => ['menu' => $option, 'info' => $row], 'msg' => '']);
        }
    }
		//delete the menu
    public function menu_del(WxinMenu $model, $id){
        if(count($model->optionlist(array('pid'=>$id)))){
            return json(['code'=>0,'data'=>[],'msg'=>'There is a submenu below, which cannot be deleted.']);
        }
        if(Db::name('wxin_menu')->delete($id)){
            $list = $model->menulist();
            return json(['code' => 1, 'data' => $list, 'msg' => 'Delete successful']);
        }else{
            return json(['code' => 0, 'data' =>[], 'msg' => 'deletion failed']);
        }
    }



    /**
     * Generate WeChat menu
     * @return
     */
    public function wxin_menu()
    {
        $model = new WxinMenu();
        $res = $model->menugenerate();
        return json($res);
    }





    public function msg_add($id)
    {
        $row = db('wxin_reply_info')->where(array('wxin_menu_id' => $id))->find();
        return json(['code' => 0, 'data' => $row, 'msg' => '']);

    }

    /**
	 * Reply content added
     */
    public function add_msg(ReplyInfo $model)
    {
        if ($this->request->isPost()) {
            $param = input('post.');
            if ($row = Db::name('wxin_reply_info')->where(array('wxin_menu_id' => $param['wxin_menu_id']))->find()) {
                $result = $model->updataData($param);
                if ($result['status'] == '1') {
                    $this->success('Save successfully', url('menu'));
                } else {
                    $this->error($result['msg']);
                }
            } else {
                unset($param['id']);
                $result = $model->saveData($param);
                if ($result['status'] == '1') {
                    $this->success('Save successfully', url('menu'));
                } else {
                    $this->error($result['msg']);
                }
            }
        } else {

        }
        $param = input('post.');
        if ($row = db('wxin_reply_info')->where(array('wxin_menu_id' => $param['wxin_menu_id']))->find()) {
            $result = $model->updataData($param);
            if ($result['status'] == '1') {
			$this->success('Save successfully', url('menu'));
            } else {
                $this->error($result['msg']);
            }
        } else {
            unset($param['id']);
            $result = $model->saveData($param);
            if ($result['status'] == '1') {
                $this->success('Save successfully', url('menu'));
            } else {
                $this->error($result['msg']);
            }
        }
    }

    /**
     * Add image and text
     */
    public function thumb_add($id)
    {
        $row = db('wxin_reply_info')->where(array('wxin_menu_id' => $id))->find();
        $this->assign('row', $row);
        $this->assign('id', $id);
        return $this->fetch();
    }
}