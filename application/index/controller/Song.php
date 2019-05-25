<?php
/**
 * Created by PhpStorm.
 * User: Viter
 * Date: 2018/3/4
 * Time: 15:41
 */

namespace app\index\controller;

use base\Base;
use ku\Algo;
use think\Db;

class Song extends Base
{

    //歌曲分类进入获取歌曲
    public function index()
    {
        $pageLimit = $this->getParam('pageLimit', 100, 'int');
        $page = $this->getParam('page', 1, 'int');
        $where = ['is_del' => 0];
        $type = $this->getParam('type', '', 'string');
        $singer = $this->getParam('singer', '', 'string');
        $language = $this->getParam('language');
        $order = '';
        if ($type) {
            $where = $this->typeToWhere($type, $where);
            $order = $this->_musicTypesCol[$type] . ' desc';
        }
        if ($singer) {
            $where['singer'] = $singer;
        }
        if ($language) {
            $where['language'] = $language;
        }
        $order .= empty($order) ? 'order_by desc' : ', order_by desc';
        $pager = Db::name('song')
            ->where($where)
            ->order($order)
            ->paginate($pageLimit, false, array('page' => $page))
            ->toArray();
        return $this->successJson($this->have_love($pager['data']));
    }

    //歌曲搜索
    public function select()
    {
        $select = $this->getParam('select');
        //是否在输入搜索的过程中
        $selectIn = $this->getParam('selectIn', 0, 'int');
        $where = 'is_del = 0 ';
        if ($select) {
            $where .= " and (singer like '%" . $select . "%' or song_name like '%" . $select . "%' or album_name like '%" . $select . "%')";
        }
        if ($selectIn) {
            $page = 1;
            $pageLimit = 5;
        } else {
            $page = $this->getParam('page', 1, 'int');
            $pageLimit = $this->getParam('pageLimit', 100, 'int');
        }
        $order = 'select_num desc ,order_by desc';
        $pager = Db::name('song')
            ->where($where)
            ->order($order)
            ->paginate($pageLimit, false, array('page' => $page))
            ->toArray();
        if ($selectIn == 0 && $select) {
            Db::name('song')
                ->where($where)
                ->setInc('select_num');
        }
        return $this->successJson($this->have_love($pager['data']));
    }

    //热播榜
    public function hot()
    {
        $pageLimit = $this->getParam('pageLimit', 100, 'int');
        $where = ['is_del' => 0];
        $order = 'played desc ,song_id desc';
        $pager = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($pageLimit)
            ->select();
        return $this->successJson($this->have_love($pager));
    }

    //热搜榜
    public function hotselect()
    {
        $pageLimit = $this->getParam('pageLimit', 100, 'int');
        $where = ['is_del' => 0];
        $order = 'select_num desc ,song_id desc';
        $pager = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($pageLimit)
            ->select();
        return $this->successJson($this->have_love($pager));
    }

    //高分榜
    public function hscore()
    {
        $pageLimit = $this->getParam('pageLimit', 100, 'int');
        $where = ['is_del' => 0];
        $order = 'comments_score desc ,song_id desc';
        $pager = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($pageLimit)
            ->select();
        return $this->successJson($this->have_love($pager));
    }

    //新歌榜
    public function newscore()
    {
//        $page = $this->getParam('page', 1, 'int');
        $pageLimit = $this->getParam('pageLimit', 100, 'int');
        $where = ['is_del' => 0, 'is_new' => 1];
        $order = 'played desc ,song_id desc';
        $pager = Db::name('song')
            ->where($where)
            ->order($order)
            ->limit($pageLimit)
            ->select();
        return $this->successJson($this->have_love($pager));
    }

    //专辑歌曲
    public function album()
    {
        $albumId = $this->getParam('albumId', 0, 'int');
        if (!$album = Db::name('album')->where(['album_id' => $albumId, 'is_del' => 0])->find()) {
            return $this->errorJson('专辑不存在');
        }
        $where = array(
            'album_id' => $albumId,
            'is_del'   => 0
        );
        $order = 'order_by desc';
        $page = $this->getParam('page', 1, 'int');
        $pageLimit = $this->getParam('pageLimit', 100, 'int');
        $pager = Db::name('song')
            ->where($where)
            ->order($order)
            ->paginate($pageLimit, false, array('page' => $page))
            ->toArray();
        return $this->successJson($this->have_love($pager['data']));
    }


    //播放操作进行日志记录
    public function played()
    {
        $songId = $this->getParam('songId', 0, 'int');
        if (!$song = Db::name('song')->where(['song_id' => $songId, 'is_del' => 0])->find()) {
            return $this->errorJson('歌曲不存在');
        }
        Db::name('song')->where('song_id',$songId)->setInc('played',1);
        $user = $this->getLoginUser();
        if (empty($user)) {
            return $this->successJson();
        }
        if (!Db::name('played_log')->where('user_id', $user['user_id'])->find()) {
            $res = Db::name('played_log')->insert(array('user_id' => $user['user_id']));
            if (!$res) {
                return $this->errorJson();
            }
        }
        $model = Db::name('played_log');
        Db::startTrans();
        if ($song['is_old']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_old', $song['is_old'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_popular']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_popular', $song['is_popular'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_dj']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_dj', $song['is_dj'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_classical']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_classical', $song['is_classical'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_flok']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_flok', $song['is_flok'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_rap']) {
            if (!$model->where('user_id', $user['user_id'])->setInc('played_rap', $song['is_rap'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }

        Db::commit();
        return $this->successJson();
    }

    //获取歌曲评论列表
    public function getcomments()
    {
        $songId = $this->getParam('songId', 0, 'int');
        if (!$song = Db::name('song')->where('song_id', $songId)->find()) {
            return $this->errorJson('歌曲不存在');
        }
        $page = $this->getParam('page', 1, 'int');
        $pageLimit = $this->getParam('pageLimit', 100, 'int');
        $where = ['song_id' => $songId];
        $order = 'create_at desc';
        $pager = Db::name('comments')
            ->where($where)
            ->order($order)
            ->paginate($pageLimit, false, array('page' => $page))
            ->toArray();
        $data = [];
        foreach ($pager['data'] as $key => $datum) {
            $datum['create_at'] = date('Y-m-d H:i:s', $datum['create_at']);
            $datum['user_name'] = $this->getUserName($datum['user_id']);
            $data[$key] = $datum;
        }
        return $this->successJson($data);
    }

    //获取推荐歌曲
    public function groom()
    {
        $user = $this->getLoginUser();
        $songs = Algo::groom($user);
        return $this->successJson($this->have_love($songs));
    }

    //判断是否收藏音乐 status=true时为在收藏表里否则不在收藏表
    public function is_love()
    {
        $user = $this->getLoginUser();
        if(empty($user)){
            return $this->successJson();
        }
        $ids = $this->getParam('ids');
        if(empty($ids)){
            return $this->errorJson('没有传入ids');
        }
        $loves = Db::name('loves')->where('`user_id`='.$user['user_id'].' and `song_id` in ('.$ids.')')->select();
        $lists = [];
        foreach ($loves as $love){
            $lists[$love['song_id']] = ['song_id'=>$love['song_id'],'is_love'=>true];
        }
        return $this->successJson($lists);

    }

    //音乐下载
    public function down()
    {
        $songId = $this->getParam('song_id',0,'int');
        $song = Db::name('song')->where('song_id',$songId)->find();
        if(!$song){
            throw new \Error('音乐不存在',404);
        }
        //获取音乐文件后缀名（文件类型）
        $typeArr = explode('.',$song['song_origin']);
        $type = end($typeArr);
        //文件真实路径
        $filepath = trim(PUBLIC_PATH.trim($song['song_origin'] ,'\\'));
        //文件大小
        $filezise = filesize($filepath);
        //设置header成下载页面
        header('Content-Type:audio/'.$type);
        header('Accept-Ranges:bytes');
        header('Accept-Length:'.$filezise);
        header('Content-Disposition:attachment;filename='.$song['song_name'].'.'.$type);
        header('Cache-Control:max-age=0');
        readfile($filepath);
        exit();
    }

}