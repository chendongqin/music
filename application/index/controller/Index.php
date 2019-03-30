<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/3/4
 * Time: 15:41
 */

namespace app\index\controller;

use base\Base;
use think\Db;

class Index extends Base
{

    public function index()
    {
        return $this->fetch();
    }


}