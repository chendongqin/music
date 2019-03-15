<?php

namespace app\admin\controller;
use base\Base;
use think\Db;
use think\Session;

class Login extends Base{

    /**后台登陆页面
     * @return mixed
     */
    public function index(){
        $adminId = Session::get('admin');
        if(!empty($adminId)){
            return $this->redirect('/admin/');
        }
        return $this->fetch();
    }

    public function i(){
        $adminId = Session::get('admin');
        if(!empty($adminId)){
            return $this->returnJson('用户已登陆',1001,true);
        }
        $useName = $this->getParam('userName','','string');
        $password = $this->getParam('password','','string');
        $admin = Db::name('admin')->where(array('login_name'=>$useName))->find();
        if(empty($admin)){
            return $this->returnJson('用户名不存在或密码错误',1000);
        }
        $res = $this->virefyPwd($admin['password'],$password);
        if(!$res){
            return $this->returnJson('用户名不存在或密码错误',1000);
        }
        Session::set('admin',$admin['id']);
        $admin['login_at'] = time();
        Db::name('admin')->update($admin);
        return $this->returnJson('登陆成功',1001,true);
    }

}