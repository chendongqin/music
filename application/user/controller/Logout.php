<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/3/4
 * Time: 15:41
 */

namespace app\user\controller;

use base\Base;
use base\Userbase;
use think\Db;
use think\Cache;

class Logout extends Userbase
{

    public function index()
    {
        $user = $this->getLoginUser();
        if($this->logoutUser($user['user_id'])){
            return $this->successJson();
        }
        return $this->errorJson();
    }


}