<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/3/4
 * Time: 15:41
 */

namespace app\user\controller;

use base\Userbase;
use think\Db;

class Index extends Userbase
{

    //获取用户信息
    public function index()
    {
        $user = $this->getLoginUser();
        $user['nick_name'] = empty($user['nick_name']) ? $user['mobile'] : $user['nick_name'];
        $user['create_at'] = date('Y-m-d H:i:s', strtotime($user['create_at']));
        unset($user['password']);
        return $this->successJson($user);
    }

    //修改密码
    public function chengepwd()
    {
        $user = $this->getLoginUser();
        $old = $this->getParam('old');
        $new = $this->getParam('new');
        $sure = $this->getParam('sure');
        if ($new != $sure) {
            return $this->errorJson('两次密码不一致');
        } elseif (strlen($new) < 6) {
            return $this->errorJson('密码长度小于6位');
        } elseif ($this->virefyPwd($user['password'], $old)) {
            return $this->errorJson('原密码不正确');
        }
        $user['password'] = $this->createPwd($new);
        $res = Db::name('user')->update($user);
        if ($res) {
            return $this->successJson();
        }
        return $this->errorJson();

    }

    //修改信息
    public function alter()
    {
        $user = $this->getLoginUser();
        $user['nick_name'] = $this->getParam('nick_name');
        // $user['language'] = $this->getParam('language', $user['language'], 'int');
        // if (!in_array($user['language'], $this->_musicLanguages)) {
        //     return $this->errorJson('修改的语言格式不正确');
        // }
        if (!$user['nick_name']) {
            return $this->errorJson('用户昵称不能为空');
        }
        $res = Db::name('user')->update($user);
        if ($res) {
            return $this->successJson();
        }
        return $this->errorJson();
    }

}