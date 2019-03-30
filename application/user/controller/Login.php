<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/3/4
 * Time: 15:41
 */

namespace app\user\controller;

use base\Base;
use think\Db;
use think\Cache;
use think\Session;

class Login extends Base
{

    public function index()
    {
        return $this->fetch();
    }

    //登陆
    public function i(){
        $mobile = $this->getParam('mobile', '');
        $password = $this->getParam('password', '');
        $code = $this->getParam('code', '');
        $virefyCode = Session::get('login_virefy_code');
        if(strtolower($virefyCode) != $code){
            return $this->errorJson('验证码不正确');
        }
        if (empty($password)) {
            return $this->errorJson('请输入密码');
        }
        $user = Db::name('user')->where(array('mobile' => $mobile))->find();
        if (empty($user)) {
            return $this->errorJson('用户不存在');
        }
        if(!$this->virefyPwd($user['password'],$password)){
            return $this->errorJson('密码不正确');
        }
        $data = array('token' => $this->userToken($user['user_id']));
        return $this->successJson($data);
    }

}