<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/3/4
 * Time: 14:56
 */
namespace app\admin\controller;
use base\Adminbase;
use think\Session;
class Logout extends Adminbase{

    //退出登陆
    public function index(){
        Session::delete('admin');
        return $this->redirect('/admin/login');
    }
}