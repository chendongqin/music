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

class Regist extends Base
{

    public function index()
    {
        return $this->fetch();
    }

    public function i()
    {
        $mobile = $this->getParam('mobile', '');
        $password = $this->getParam('password', '');
        $sure = $this->getParam('sure', '');
        $code = $this->getParam('code', '');
        $virefyCode = Cache::get('regist' . $mobile);
        if ($virefyCode != $code) {
            return $this->errorJson('手机验证码不正确');
        }
        if (empty($mobile) || empty($password) || empty($sure) || empty($code)) {
            return $this->errorJson('请填写所有注册信息');
        }
        $user = Db::name('user')->where(array('mobile' => $mobile))->find();
        if (!empty($user)) {
            return $this->errorJson('该手机已注册');
        }
        if ($sure != $password) {
            return $this->errorJson('两次密码不一致');
        }
        if (strlen($password) < 6 or strlen($password) > 30) {
            return $this->errorJson('密码长度在6到30位之间');
        }
        $pwd = $this->createPwd($password);
        $res = Db::name('user')->insert(
            array(
                'mobile'    => $mobile,
                'password'  => $pwd,
                'create_at' => date('YmdHis'),
            )
        );
        if (!$res) {
            return $this->errorJson();
        }
        $userId = Db::name('user')->getLastInsID();
        $data = array('token' => $this->userToken($userId));
        return $this->successJson($data);
    }

    public function show()
    {
        $channel = $this->getParam('channel');
        $this->assign('channel', $channel);
        return $this->fetch();
    }


}