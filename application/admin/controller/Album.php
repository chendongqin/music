<?php

namespace app\admin\controller;

use base\Adminbase;
use ku\Tool;
use think\Db;

class Album extends Adminbase
{
    //专辑列表
    public function index()
    {
        $name = $this->getParam('name', '', 'string');
        $pageLimit = $this->getParam('pageLimit', 15, 'int');
        $page = $this->getParam('page', 1, 'int');
        $is_new = $this->getParam('is_new', 100, 'int');
        $isdel = $this->getParam('isdel', 0, 'int');
        $where['is_del'] = $isdel;
        if ($is_new != 100) {
            $where['is_new'] = $is_new;
        }
        if ($name) {
            $where['album_name|author'] = array('like', $name . '%');
        }
        $language = $this->getParam('language', '', 'string');
        if ($language) {
            $where['language'] = $language;
        }
        $pager = Db::name('album')->where($where)->paginate($pageLimit, false, array('page' => $page))->toArray();
        $this->assign('pager', $pager);
        $this->assign('pageLimit', $pageLimit);
        $this->assign('page', $page);
        $this->assign('musicLanguages', $this->_musicLanguages);
        return $this->fetch();
    }

    //添加专辑
    public function add()
    {
        $data['album_name'] = $this->getParam('album_name');
        $data['author'] = $this->getParam('author');
        $data['is_new'] = $this->getParam('is_new', 1, 'int');
        $data['language'] = $this->getParam('language');
        $issueDate = $this->getParam('issue_time', date('Y-m-d'));
        $data['issue_time'] = date('Y-m-d', strtotime($issueDate));
        $data['order_by'] = $this->getParam('order_by', 0, 'int');
        if (empty($data['album_name']) || empty($data['author'])) {
            return $this->returnJson('歌名和歌手不能为空');
        }
        if ($exist = Db::name('album')->where(array('album_name' => $data['album_name'], 'author' => $data['author'], 'is_del' => 0))->find()) {
            return $this->returnJson('已存在该歌手的同名专辑');
        }
        if (!in_array($data['language'], $this->_musicLanguages))
            $data['language'] = '其他';
        //上传音乐图片
        $request = $this->request;
        $songOrigin = $request->file('albumPicture');
        if (empty($songOrigin)) {
            return $this->returnJson('专辑图片不能为空');
        }
        $dir = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'music_picture';
        Tool::makeDir($dir);
        $info = $songOrigin->move($dir);
        $filename = $info->getFilename();
        if (!$filename) {
            return $this->returnJson($songOrigin->getError());
        }
        $filenameArr = explode('.', $filename);
        if (!in_array(strtoupper(end($filenameArr)), $this->_musicOrigins)) {
            @unlink(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'music_picture' . DS . date('Ym') . DS . $filename);
            return $this->returnJson('图片文件类型为：' . implode('，', $this->_musicOrigins));
        }
        $data['album_picture'] = DS . 'uploads' . DS . 'music_picture' . DS . date('Ym') . DS . $filename;
        Db::startTrans();
        if (!$singer = Db::name('singer')->where('singer_name', $data['author'])->find()) {
            $res = Db::name('singer')->insert(array('singer_name' => $data['author']));
            if (!$res) {
                Db::rollback();
                return $this->returnJson('失败');
            }
        }
        $res = Db::name('album')->insert($data);
        if (!$res) {
            Db::rollback();
            return $this->returnJson('失败');
        }
        Db::commit();
        return $this->returnJson('成功', 1001, true);
    }

    //数据更新
    public function update()
    {
        $album_id = $this->getParam('album_id', 0, 'int');
        if (!$album = Db::name('song')->where('song_id', $album_id)->find()) {
            return $this->returnJson('歌曲不存在');
        }
        $album['album_name'] = $this->getParam('album_name');
        $album['author'] = $this->getParam('author');
        $album['is_new'] = $this->getParam('is_new', 1, 'int');
        $album['language'] = $this->getParam('language');
        $issueDate = $this->getParam('issue_time', date('Y-m-d'));
        $album['issue_time'] = date('Y-m-d', strtotime($issueDate));
        $album['order_by'] = $this->getParam('order_by', 0, 'int');
        if (empty($album['album_name']) || empty($album['author'])) {
            return $this->returnJson('专辑名和歌手不能为空');
        }
        if ($exist = Db::name('album')->where(array('album_name' => $album['album_name'], 'author' => $album['author'], 'is_del' => 0, 'album_id' => array('<>', $album_id)))->find()) {
            return $this->returnJson('已存在该歌手的同名专辑');
        }
        if (!in_array($album['language'], $this->_musicLanguages))
            $album['language'] = '其他';
        $res = Db::name('album')->update($album);
        if (!$res) {
            return $this->returnJson('失败');
        }
        return $this->returnJson('成功', 1001, true);
    }

    //删除
    public function delete()
    {
        $album_id = $this->getParam('album_id', 0, 'int');
        if (!$album = Db::name('song')->where('song_id', $album_id)->find()) {
            return $this->returnJson('歌曲不存在');
        }
        $album['is_del'] = 1;
        $res = Db::name('album')->update($album);
        if (!$res) {
            return $this->returnJson('失败');
        }
        return $this->returnJson('成功', 1001, true);
    }

    //歌曲添加至专辑
    public function addsong()
    {
        $song_ids = $this->getParam('song_ids', '', 'string');
        if (empty($song_ids))
            return $this->returnJson('加入专辑的歌曲不能为空');
        $album_id = $this->getParam('album_id', 0, 'int');
        if (!$album = Db::name('song')->where('song_id', $album_id)->find()) {
            return $this->returnJson('歌曲不存在');
        }
        $res = Db::name('song')
            ->where('song_id in(' . $song_ids . ')')
            ->update(array('album_id' => $album_id, 'album_name' => $album['album_name']));
        if (!$res) {
            return $this->returnJson('失败');
        }
        return $this->returnJson('成功', 1001, true);
    }

}