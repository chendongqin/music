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

class Album extends Base
{

    //专辑列表
    public function index()
    {
        $where = 'is_del=0 ';
        $select = $this->getParam('select');
        if ($select) {
            $where .= " and (album_name like '%".$select."%' or author like '%".$select."%') ";
        }
        $language = $this->getParam('language');
        if ($language) {
            $where .= ' and language='.$language;
        }
        $isNew = $this->getParam('isNew',0,'int');
        if ($isNew) {
            $where .= ' and is_new=1';
        }
        $order = 'is_new desc,order_by desc';
        $page = $this->getParam('page', 1, 'int');
        $pageLimit = $this->getParam('pageLimit', 20, 'int');
        $pager = Db::name('album')
            ->where($where)
            ->order($order)
            ->paginate($pageLimit, false, array('page' => $page))
            ->toArray();
        return $this->successJson($pager['data']);
    }


}