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

class Song extends Userbase
{

    public function index()
    {
        return $this->fetch();
    }

    //设置兴趣爱好
    public function interesting()
    {
        $data = [];
        $user = $this->getLoginUser();
        $singers = $this->getParam('singers', '', 'string');
        $singersArr = explode(',', $singers);
        if (count($singersArr) > 5) {
            return $this->errorJson('喜欢的歌手不能超过五个');
        }
        $data['love_singer'] = $singers;
        $data['love_old'] = $this->getParam('love_old', 0, 'int');
        $data['love_popular'] = $this->getParam('love_popular', 0, 'int');
        $data['love_flok'] = $this->getParam('love_flok', 0, 'int');
        $data['love_rap'] = $this->getParam('love_rap', 0, 'int');
        $data['love_dj'] = $this->getParam('love_dj', 0, 'int');
        $data['love_new'] = $this->getParam('love_new', 0, 'int');
        $data['love_classical'] = $this->getParam('love_classical', 0, 'int');
        if (Db::name('interest')->where('user_id', $user['user_id'])->find()) {
            $res = Db::name('interest')
                ->where('user_id', $user['user_id'])
                ->update($data);
        } else {
            $data['user_id'] = $user['user_id'];
            $res = Db::name('interest')->insert($data);
        }
        if (!$res) {
            return $this->errorJson();
        }
        return $this->successJson();
    }

    //喜爱音乐列表
    public function lovelists()
    {
        $page = $this->getParam('page', 1, 'int');
        $pageLimit = $this->getParam('pageLimit', 100, 'int');
        $user = $this->getLoginUser();
        $where = ['s.is_del' => 0, 'l.user_id' => $user['user_id']];
        $order = 'l.create_at desc';
        $join = [
            ['mu_song s', 's.song_id = l.song_id', 'LEFT'],
        ];
        $pager = Db::table('mu_loves')
            ->alias('l')
            ->join($join)
            ->where($where)
            ->order($order)
            ->paginate($pageLimit, false, array('page' => $page))
            ->toArray();
        return $this->successJson($pager['data']);
    }

    //收藏音乐
    public function love()
    {
        $user = $this->getLoginUser();
        if (!Db::name('loves_log')->where('user_id', $user['user_id'])->find()) {
            $res = Db::name('loves_log')->insert(array('user_id' => $user['user_id']));
            if (!$res) {
                return $this->errorJson();
            }
        }
        $ids = $this->getParam('ids', '', 'string');
        if (is_numeric($ids)) {
            if (!$song = Db::name('song')->where(['song_id' => $ids, 'is_del' => 0])->find()) {
                return $this->errorJson('歌曲不存在');
            }
            $res = $this->addLove($user, $song);
            if (!$res) {
                return $this->errorJson('添加失败');
            }
        } else {
            $ids = explode(',', $ids);
            $fail = 0;
            $songs = [];
            foreach ($ids as $id) {
                if (!$song = Db::name('song')->where(['song_id' => $id, 'is_del' => 0])->find()) {
                    return $this->errorJson('歌曲不存在');
                }
                $songs[] = $song;
            }
            foreach ($songs as $s) {
                $res = $this->addLove($user, $s);
                if (!$res) {
                    $fail++;
                }
            }
            if ($fail) {
                return $this->errorJson($fail . '首歌曲添加失败');
            }
        }
        return $this->successJson();
    }

    //删除收藏音乐
    public function dellove()
    {
        $user = $this->getLoginUser();
        $songId = $this->getParam('songId', 0, 'int');
        if (!$love = Db::name('loves')->where(['user_id' => $user['user_id'], 'song_id' => $songId])->find()) {
            return $this->errorJson('喜欢的歌曲不存在');
        }
        $song = Db::name('song')->where(['song_id' => $songId])->find();
        if(!$song){
            return $this->errorJson('没有该收藏');
        }
        Db::startTrans();
        $res = Db::name('loves')->delete($love['id']);
        if (!$res) {
            Db::rollback();
            return $this->errorJson();
        }
        $model = Db::name('loves_log');
        if ($song['is_old']) {
            if (!$model->where('user_id', $user['user_id'])->setDec('love_old', $song['is_old'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_popular']) {
            if (!$model->where('user_id', $user['user_id'])->setDec('love_popular', $song['is_popular'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_dj']) {
            if (!$model->where('user_id', $user['user_id'])->setDec('love_dj', $song['is_dj'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_classical']) {
            if (!$model->where('user_id', $user['user_id'])->setDec('love_classical', $song['is_classical'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_flok']) {
            if (!$model->where('user_id', $user['user_id'])->setDec('love_flok', $song['is_flok'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        if ($song['is_rap']) {
            if (!$model->where('user_id', $user['user_id'])->setDec('love_rap', $song['is_rap'])) {
                Db::rollback();
                return $this->errorJson();
            }
        }
        Db::commit();
        return $this->successJson();
    }

    //评论歌曲
    public function comment()
    {
        $songId = $this->getParam('songId', 0, 'int');
        if (!$song = Db::name('song')->where(['song_id' => $songId, 'is_del' => 0])->find()) {
            return $this->errorJson('没有改歌曲');
        }
        $user = $this->getLoginUser();
        $comment = $this->getParam('comment');
        $score = $this->getParam('score');
        if (!is_numeric($score) || $score > 10) {
            return $this->errorJson('评分数据错误');
        }
        $avgScore = ($song['comments_score'] + $score) / ($song['comments_num'] + 1);
        Db::startTrans();
        $data = array(
            'user_id'   => $user['user_id'],
            'song_id'   => $songId,
            'describe'  => $comment,
            'score'     => $score,
            'create_at' => time()
        );
        $res = Db::name('comments')->insert($data);
        if (!$res) {
            Db::rollback();
            return $this->errorJson('评论失败');
        }
        $res = Db::name('song')
            ->where('song_id', $songId)
            ->setInc('comments_num');
        if (!$res) {
            Db::rollback();
            return $this->errorJson('评论失败');
        }
        $res = Db::name('song')
            ->where('song_id', $songId)
            ->update(array('comments_score' => number_format($avgScore, 1, '.', '')));
        if (!$res) {
            Db::rollback();
            return $this->errorJson('评论失败');
        }
        if (!$res) {
            Db::rollback();
            return $this->errorJson('评论失败');
        }
        Db::commit();
        return $this->successJson();
    }

    //播放列表
    public function playlist(){
        $user = $this->getLoginUser();
        $page = $this->getParam('page', 1, 'int');
        $pageLimit = $this->getParam('pageLimit', 100, 'int');
        $where = ['user_id' => $user['user_id']];
        $order = 'list_id desc';
        $pager = Db::table('mu_loves')
            ->where($where)
            ->order($order)
            ->paginate($pageLimit, false, array('page' => $page))
            ->toArray();
        $data = [];
        foreach ($pager['data'] as $datum){
            $song = Db::name('song')->where('song_id',$datum['song_id'])->find();
            $song['list_id'] = $datum['list_id'];
            $data[] = $song;
        }
        return $this->successJson($data);
    }

    //添加播放歌曲ids为歌曲id用,隔开
    public function addplaylist(){
        $user = $this->getLoginUser();
        $ids = $this->getParam('ids','','string');
        $ids = explode(',',$ids);
        if(empty($ids)){
            return $this->errorJson('没有选择音乐');
        }
        $insertData = ['user_id'=>$user['user_id']];
        foreach ($ids as $id){
            if(Db::name('song')->where(['song_id'=>$id,'is_del'=>0])){
                return $this->errorJson('参数错误');
            }
            $exist = Db::name('play_list')->where(['user_id'=>$user['user_id'],'song_id'=>$id])->find();
            if($exist){
                continue;
            }
            $insertData['song_id'] = $id;
            Db::name('play_list')->insert($insertData);
        }
        return $this->successJson('成功');
    }

    //删除播放歌曲ids为歌曲id用,隔开
    public function delplaylist(){
        $user = $this->getLoginUser();
        $ids = $this->getParam('ids','','string');
        $res = Db::name('play_list')->where('list_id in('.$ids.') and user_id='.$user['user_id'])->delete();
        if($res){
            return $this->successJson('成功');
        }
        return $this->errorJson();
    }

    //清空播放列表
    public function delall(){
        $user = $this->getLoginUser();
        $res = Db::name('play_list')->where('user_id',$user['user_id'])->delete();
        if($res){
            return $this->successJson('成功');
        }
        return $this->errorJson();
    }

}