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

class Data extends Base
{

    public function index()
    {
        return $this->successJson();
    }

    //获取音乐属性
    public function getSongType()
    {
        return $this->successJson(['types' => $this->_musicTypes]);
    }

    //获取音乐属性
    public function getSongLanguage()
    {
        return $this->successJson(['languages' => $this->_musicLanguages]);
    }

}