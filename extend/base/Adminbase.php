<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/2/14
 * Time: 13:15
 */
namespace base;
use base\Base;
use think\Db;
use think\Session;
class Adminbase extends Base{

    protected function _initialize() {
        $session = new Session();
        $adminId = $session->get('admin');
        if(empty($adminId)){
            return $this->redirect('/admin/login');
        }
        $admin = Db::name('admin')->where('id',$adminId)->find();
        $this->assign('admin',$admin);
    }


}